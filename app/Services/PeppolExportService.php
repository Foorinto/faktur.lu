<?php

namespace App\Services;

use App\Models\Invoice;
use DOMDocument;
use DOMElement;

class PeppolExportService
{
    // Peppol BIS 3.0 identifiers
    protected const CUSTOMIZATION_ID = 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0';
    protected const PROFILE_ID = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';

    // UBL 2.1 namespaces
    protected const NS_INVOICE = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
    protected const NS_CREDIT_NOTE = 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2';
    protected const NS_CAC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
    protected const NS_CBC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

    // Invoice type codes (UNCL1001)
    protected const TYPE_INVOICE = '380';
    protected const TYPE_CREDIT_NOTE = '381';

    /**
     * Schéma d'identifiant Peppol du numéro de TVA luxembourgeois (liste EAS).
     *
     * Nommé plutôt qu'écrit en clair parce que la valeur a été fausse partout :
     * 9934 désigne la Croatie et 0184 le Danemark. Les identifiants de test
     * publiés par l'État (9938:LU10889245-TEST) confirment celui-ci.
     */
    public const SCHEME_LU_VAT = '9938';

    // Tax exemption reasons
    protected const TAX_EXEMPTION_REASONS = [
        'K' => 'Intra-community supply',
        'AE' => 'Reverse charge',
        'G' => 'Export outside the EU',
        'E' => 'Exempt from tax',
        'O' => 'Services outside scope of tax',
    ];

    /**
     * Generate Peppol BIS 3.0 XML for an invoice.
     */
    public function generate(Invoice $invoice): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        // Determine if credit note
        $isCreditNote = $invoice->isCreditNote();
        $rootNs = $isCreditNote ? self::NS_CREDIT_NOTE : self::NS_INVOICE;
        $rootTag = $isCreditNote ? 'CreditNote' : 'Invoice';

        // Create root element with namespaces
        $root = $doc->createElementNS($rootNs, $rootTag);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', self::NS_CAC);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', self::NS_CBC);
        $doc->appendChild($root);

        // Determine tax category for special handling
        $taxCategoryCode = $this->getMainTaxCategoryCode($invoice);

        // Add document content
        $this->addDocumentHeader($doc, $root, $invoice, $isCreditNote);
        $this->addAccountingSupplierParty($doc, $root, $invoice);
        $this->addAccountingCustomerParty($doc, $root, $invoice);

        // Add Delivery for intra-community supplies
        if (in_array($taxCategoryCode, ['K', 'G'])) {
            $this->addDelivery($doc, $root, $invoice);
        }

        $this->addPaymentMeans($doc, $root, $invoice);
        $this->addDocumentAllowances($doc, $root, $invoice);
        $this->addTaxTotal($doc, $root, $invoice);
        $this->addLegalMonetaryTotal($doc, $root, $invoice, $isCreditNote);
        $this->addInvoiceLines($doc, $root, $invoice, $isCreditNote);

        return $doc->saveXML();
    }

    /**
     * Add document header elements.
     */
    protected function addDocumentHeader(DOMDocument $doc, DOMElement $root, Invoice $invoice, bool $isCreditNote): void
    {
        // CustomizationID
        $this->addCbcElement($doc, $root, 'CustomizationID', self::CUSTOMIZATION_ID);

        // ProfileID
        $this->addCbcElement($doc, $root, 'ProfileID', self::PROFILE_ID);

        // ID (invoice number)
        $this->addCbcElement($doc, $root, 'ID', $invoice->number);

        // IssueDate
        $this->addCbcElement($doc, $root, 'IssueDate', $invoice->issued_at->format('Y-m-d'));

        // DueDate (only for invoices, NOT allowed in credit notes)
        if (!$isCreditNote && $invoice->due_at) {
            $this->addCbcElement($doc, $root, 'DueDate', $invoice->due_at->format('Y-m-d'));
        }

        // InvoiceTypeCode or CreditNoteTypeCode
        $typeCode = $isCreditNote ? self::TYPE_CREDIT_NOTE : self::TYPE_INVOICE;
        $typeElement = $isCreditNote ? 'CreditNoteTypeCode' : 'InvoiceTypeCode';
        $this->addCbcElement($doc, $root, $typeElement, $typeCode);

        // DocumentCurrencyCode
        $this->addCbcElement($doc, $root, 'DocumentCurrencyCode', $invoice->currency ?? 'EUR');

        // BuyerReference (MANDATORY in Peppol) - use invoice number as fallback
        $buyerRef = $invoice->payment_reference ?: $invoice->number;
        $this->addCbcElement($doc, $root, 'BuyerReference', $buyerRef);

        // BillingReference for credit notes
        if ($isCreditNote && $invoice->credit_note_for) {
            $originalInvoice = Invoice::find($invoice->credit_note_for);
            if ($originalInvoice) {
                $billingRef = $doc->createElementNS(self::NS_CAC, 'cac:BillingReference');
                $invoiceDocRef = $doc->createElementNS(self::NS_CAC, 'cac:InvoiceDocumentReference');
                $this->addCbcElement($doc, $invoiceDocRef, 'ID', $originalInvoice->number);
                if ($originalInvoice->issued_at) {
                    $this->addCbcElement($doc, $invoiceDocRef, 'IssueDate', $originalInvoice->issued_at->format('Y-m-d'));
                }
                $billingRef->appendChild($invoiceDocRef);
                $root->appendChild($billingRef);
            }
        }
    }

    /**
     * Add AccountingSupplierParty (seller).
     */
    protected function addAccountingSupplierParty(DOMDocument $doc, DOMElement $root, Invoice $invoice): void
    {
        $seller = $invoice->seller;

        $supplierParty = $doc->createElementNS(self::NS_CAC, 'cac:AccountingSupplierParty');
        $party = $doc->createElementNS(self::NS_CAC, 'cac:Party');

        // EndpointID (Peppol ID) - use VAT number with proper scheme
        $endpointId = $this->getEndpointId($seller);
        $endpointScheme = $this->getEndpointScheme($seller);

        $endpoint = $this->addCbcElement($doc, $party, 'EndpointID', $endpointId);
        $endpoint->setAttribute('schemeID', $endpointScheme);

        // PartyIdentification (additional IDs like RCS)
        if (!empty($seller['rcs_number'])) {
            $partyId = $doc->createElementNS(self::NS_CAC, 'cac:PartyIdentification');
            $this->addCbcElement($doc, $partyId, 'ID', $seller['rcs_number']);
            $party->appendChild($partyId);
        }

        // PartyName
        $partyName = $doc->createElementNS(self::NS_CAC, 'cac:PartyName');
        $this->addCbcElement($doc, $partyName, 'Name', $seller['company_name'] ?? $seller['legal_name']);
        $party->appendChild($partyName);

        // PostalAddress
        $this->addPostalAddress($doc, $party, [
            'address' => $seller['address'] ?? '',
            'city' => $seller['city'] ?? '',
            'postal_code' => $seller['postal_code'] ?? '',
            'country_code' => $seller['country_code'] ?? 'LU',
        ]);

        // PartyTaxScheme (VAT)
        if (!empty($seller['vat_number'])) {
            $this->addPartyTaxScheme($doc, $party, $seller['vat_number']);
        }

        // PartyLegalEntity
        $legalEntity = $doc->createElementNS(self::NS_CAC, 'cac:PartyLegalEntity');
        $this->addCbcElement($doc, $legalEntity, 'RegistrationName', $seller['legal_name'] ?? $seller['company_name']);
        if (!empty($seller['matricule'])) {
            $this->addCbcElement($doc, $legalEntity, 'CompanyID', $seller['matricule']);
        }
        $party->appendChild($legalEntity);

        // Contact (optional)
        if (!empty($seller['email']) || !empty($seller['phone'])) {
            $contact = $doc->createElementNS(self::NS_CAC, 'cac:Contact');
            if (!empty($seller['phone'])) {
                $this->addCbcElement($doc, $contact, 'Telephone', $seller['phone']);
            }
            if (!empty($seller['email'])) {
                $this->addCbcElement($doc, $contact, 'ElectronicMail', $seller['email']);
            }
            $party->appendChild($contact);
        }

        $supplierParty->appendChild($party);
        $root->appendChild($supplierParty);
    }

    /**
     * Add AccountingCustomerParty (buyer).
     */
    protected function addAccountingCustomerParty(DOMDocument $doc, DOMElement $root, Invoice $invoice): void
    {
        $buyer = $invoice->buyer;

        $customerParty = $doc->createElementNS(self::NS_CAC, 'cac:AccountingCustomerParty');
        $party = $doc->createElementNS(self::NS_CAC, 'cac:Party');

        // EndpointID (Peppol ID) - use VAT number with proper scheme
        $endpointId = $this->getEndpointId($buyer);
        $endpointScheme = $this->getEndpointScheme($buyer);

        $endpoint = $this->addCbcElement($doc, $party, 'EndpointID', $endpointId);
        $endpoint->setAttribute('schemeID', $endpointScheme);

        // PartyIdentification
        if (!empty($buyer['registration_number'])) {
            $partyId = $doc->createElementNS(self::NS_CAC, 'cac:PartyIdentification');
            $this->addCbcElement($doc, $partyId, 'ID', $buyer['registration_number']);
            $party->appendChild($partyId);
        }

        // PartyName
        $partyName = $doc->createElementNS(self::NS_CAC, 'cac:PartyName');
        $this->addCbcElement($doc, $partyName, 'Name', $buyer['name']);
        $party->appendChild($partyName);

        // PostalAddress
        $this->addPostalAddress($doc, $party, [
            'address' => $buyer['address'] ?? '',
            'city' => $buyer['city'] ?? '',
            'postal_code' => $buyer['postal_code'] ?? '',
            'country_code' => $buyer['country_code'] ?? 'LU',
        ]);

        // PartyTaxScheme (VAT)
        if (!empty($buyer['vat_number'])) {
            $this->addPartyTaxScheme($doc, $party, $buyer['vat_number']);
        }

        // PartyLegalEntity
        $legalEntity = $doc->createElementNS(self::NS_CAC, 'cac:PartyLegalEntity');
        $this->addCbcElement($doc, $legalEntity, 'RegistrationName', $buyer['name']);
        $party->appendChild($legalEntity);

        // Contact
        if (!empty($buyer['email']) || !empty($buyer['phone'])) {
            $contact = $doc->createElementNS(self::NS_CAC, 'cac:Contact');
            if (!empty($buyer['phone'])) {
                $this->addCbcElement($doc, $contact, 'Telephone', $buyer['phone']);
            }
            if (!empty($buyer['email'])) {
                $this->addCbcElement($doc, $contact, 'ElectronicMail', $buyer['email']);
            }
            $party->appendChild($contact);
        }

        $customerParty->appendChild($party);
        $root->appendChild($customerParty);
    }

    /**
     * Add Delivery element (required for intra-community supplies).
     */
    protected function addDelivery(DOMDocument $doc, DOMElement $root, Invoice $invoice): void
    {
        $buyer = $invoice->buyer;

        $delivery = $doc->createElementNS(self::NS_CAC, 'cac:Delivery');

        // ActualDeliveryDate - use invoice issue date as fallback
        $deliveryDate = $invoice->issued_at->format('Y-m-d');
        $this->addCbcElement($doc, $delivery, 'ActualDeliveryDate', $deliveryDate);

        // DeliveryLocation with Country (required for intra-community)
        $deliveryLocation = $doc->createElementNS(self::NS_CAC, 'cac:DeliveryLocation');
        $address = $doc->createElementNS(self::NS_CAC, 'cac:Address');
        $country = $doc->createElementNS(self::NS_CAC, 'cac:Country');
        $this->addCbcElement($doc, $country, 'IdentificationCode', $buyer['country_code'] ?? 'LU');
        $address->appendChild($country);
        $deliveryLocation->appendChild($address);
        $delivery->appendChild($deliveryLocation);

        $root->appendChild($delivery);
    }

    /**
     * Add PaymentMeans.
     */
    protected function addPaymentMeans(DOMDocument $doc, DOMElement $root, Invoice $invoice): void
    {
        $seller = $invoice->seller;

        if (empty($seller['iban'])) {
            return;
        }

        $paymentMeans = $doc->createElementNS(self::NS_CAC, 'cac:PaymentMeans');

        // PaymentMeansCode (30 = Credit transfer, 58 = SEPA credit transfer)
        $this->addCbcElement($doc, $paymentMeans, 'PaymentMeansCode', '30');

        // PayeeFinancialAccount
        $account = $doc->createElementNS(self::NS_CAC, 'cac:PayeeFinancialAccount');
        $this->addCbcElement($doc, $account, 'ID', str_replace(' ', '', $seller['iban']));

        if (!empty($seller['bank_name'])) {
            $this->addCbcElement($doc, $account, 'Name', $seller['bank_name']);
        }

        // FinancialInstitutionBranch (BIC)
        if (!empty($seller['bic'])) {
            $branch = $doc->createElementNS(self::NS_CAC, 'cac:FinancialInstitutionBranch');
            $this->addCbcElement($doc, $branch, 'ID', $seller['bic']);
            $account->appendChild($branch);
        }

        $paymentMeans->appendChild($account);
        $root->appendChild($paymentMeans);
    }

    /**
     * Add TaxTotal with subtotals per VAT rate.
     */
    protected function addTaxTotal(DOMDocument $doc, DOMElement $root, Invoice $invoice): void
    {
        $taxTotal = $doc->createElementNS(self::NS_CAC, 'cac:TaxTotal');

        // TaxAmount (total VAT)
        $taxAmount = $this->addCbcElement($doc, $taxTotal, 'TaxAmount', $this->formatAmount($invoice->total_vat));
        $taxAmount->setAttribute('currencyID', $invoice->currency ?? 'EUR');

        // TaxSubtotal per VAT rate
        $vatBreakdown = $invoice->vat_breakdown;
        foreach ($vatBreakdown as $amounts) {
            $rate = $amounts['rate'];
            $taxSubtotal = $doc->createElementNS(self::NS_CAC, 'cac:TaxSubtotal');

            // TaxableAmount (base HT)
            $taxableAmount = $this->addCbcElement($doc, $taxSubtotal, 'TaxableAmount', $this->formatAmount($amounts['base']));
            $taxableAmount->setAttribute('currencyID', $invoice->currency ?? 'EUR');

            // TaxAmount (VAT for this rate)
            $subTaxAmount = $this->addCbcElement($doc, $taxSubtotal, 'TaxAmount', $this->formatAmount($amounts['amount']));
            $subTaxAmount->setAttribute('currencyID', $invoice->currency ?? 'EUR');

            // TaxCategory
            $taxCategory = $doc->createElementNS(self::NS_CAC, 'cac:TaxCategory');
            $categoryCode = $this->getTaxCategoryCode($rate, $invoice);
            $this->addCbcElement($doc, $taxCategory, 'ID', $categoryCode);
            $this->addCbcElement($doc, $taxCategory, 'Percent', $rate);

            // Add TaxExemptionReason for exempt categories
            if (isset(self::TAX_EXEMPTION_REASONS[$categoryCode])) {
                $this->addCbcElement($doc, $taxCategory, 'TaxExemptionReason', self::TAX_EXEMPTION_REASONS[$categoryCode]);
            }

            // TaxScheme
            $taxScheme = $doc->createElementNS(self::NS_CAC, 'cac:TaxScheme');
            $this->addCbcElement($doc, $taxScheme, 'ID', 'VAT');
            $taxCategory->appendChild($taxScheme);

            $taxSubtotal->appendChild($taxCategory);
            $taxTotal->appendChild($taxSubtotal);
        }

        $root->appendChild($taxTotal);
    }

    /**
     * Add document-level allowances (BG-20) for global discounts, one per VAT rate,
     * each linked to its tax category so the taxable amounts reconcile.
     */
    protected function addDocumentAllowances(DOMDocument $doc, DOMElement $root, Invoice $invoice): void
    {
        $totals = app(\App\Services\DocumentTotalsCalculator::class)->compute($invoice->items, $invoice->discounts);
        $reason = $invoice->discounts->pluck('label')->filter()->implode(', ') ?: 'Remise';
        $currency = $invoice->currency ?? 'EUR';

        foreach ($totals['rates'] as $rateBreakdown) {
            if (bccomp($rateBreakdown['discount'], '0', 4) !== 1) {
                continue;
            }
            $rate = (float) $rateBreakdown['rate'];

            $allowance = $doc->createElementNS(self::NS_CAC, 'cac:AllowanceCharge');
            $this->addCbcElement($doc, $allowance, 'ChargeIndicator', 'false');
            $this->addCbcElement($doc, $allowance, 'AllowanceChargeReason', $reason);
            $amountEl = $this->addCbcElement($doc, $allowance, 'Amount', $this->formatAmount($rateBreakdown['discount']));
            $amountEl->setAttribute('currencyID', $currency);

            $taxCategory = $doc->createElementNS(self::NS_CAC, 'cac:TaxCategory');
            $this->addCbcElement($doc, $taxCategory, 'ID', $this->getTaxCategoryCode($rate, $invoice));
            $this->addCbcElement($doc, $taxCategory, 'Percent', $rate);
            $taxScheme = $doc->createElementNS(self::NS_CAC, 'cac:TaxScheme');
            $this->addCbcElement($doc, $taxScheme, 'ID', 'VAT');
            $taxCategory->appendChild($taxScheme);
            $allowance->appendChild($taxCategory);

            $root->appendChild($allowance);
        }
    }

    /**
     * Add LegalMonetaryTotal.
     */
    protected function addLegalMonetaryTotal(DOMDocument $doc, DOMElement $root, Invoice $invoice, bool $isCreditNote): void
    {
        $monetary = $doc->createElementNS(self::NS_CAC, 'cac:LegalMonetaryTotal');
        $currency = $invoice->currency ?? 'EUR';
        $totals = app(\App\Services\DocumentTotalsCalculator::class)->compute($invoice->items, $invoice->discounts);

        // LineExtensionAmount (BT-106) = sum of line net amounts (before document discounts)
        $lineExt = $this->addCbcElement($doc, $monetary, 'LineExtensionAmount', $this->formatAmount($totals['subtotal_ht']));
        $lineExt->setAttribute('currencyID', $currency);

        // TaxExclusiveAmount (BT-109) = net HT (after document discounts)
        $taxExcl = $this->addCbcElement($doc, $monetary, 'TaxExclusiveAmount', $this->formatAmount($invoice->total_ht));
        $taxExcl->setAttribute('currencyID', $currency);

        // TaxInclusiveAmount (total TTC)
        $taxIncl = $this->addCbcElement($doc, $monetary, 'TaxInclusiveAmount', $this->formatAmount($invoice->total_ttc));
        $taxIncl->setAttribute('currencyID', $currency);

        // AllowanceTotalAmount (BT-107) = sum of document-level discounts
        if (bccomp($totals['discount_total'], '0', 4) === 1) {
            $allowanceTotal = $this->addCbcElement($doc, $monetary, 'AllowanceTotalAmount', $this->formatAmount($totals['discount_total']));
            $allowanceTotal->setAttribute('currencyID', $currency);
        }

        // PayableAmount (amount to pay)
        $payable = $this->addCbcElement($doc, $monetary, 'PayableAmount', $this->formatAmount($invoice->total_ttc));
        $payable->setAttribute('currencyID', $currency);

        $root->appendChild($monetary);
    }

    /**
     * Add invoice lines.
     */
    protected function addInvoiceLines(DOMDocument $doc, DOMElement $root, Invoice $invoice, bool $isCreditNote): void
    {
        $lineTag = $isCreditNote ? 'cac:CreditNoteLine' : 'cac:InvoiceLine';
        $qtyTag = $isCreditNote ? 'CreditedQuantity' : 'InvoicedQuantity';

        $lineNumber = 1;
        foreach ($invoice->items as $item) {
            $line = $doc->createElementNS(self::NS_CAC, $lineTag);

            // ID
            $this->addCbcElement($doc, $line, 'ID', (string) $lineNumber);

            // Quantity
            $qty = $this->addCbcElement($doc, $line, $qtyTag, $this->formatQuantity($item->quantity));
            $qty->setAttribute('unitCode', $this->mapUnitCode($item->unit));

            // LineExtensionAmount (line total HT)
            $lineAmount = $this->addCbcElement($doc, $line, 'LineExtensionAmount', $this->formatAmount($item->total_ht));
            $lineAmount->setAttribute('currencyID', $invoice->currency ?? 'EUR');

            // Line-level discount (BG-27) : keep the gross unit price in Price and
            // subtract the discount as a line allowance so that
            // LineExtensionAmount = (PriceAmount × qty) − allowance holds exactly.
            $grossLine = bcmul((string) $item->unit_price, (string) $item->quantity, 4);
            $allowanceAmount = bcsub($grossLine, (string) $item->total_ht, 4);
            if (bccomp($allowanceAmount, '0', 4) === 1) {
                $allowanceEl = $doc->createElementNS(self::NS_CAC, 'cac:AllowanceCharge');
                $this->addCbcElement($doc, $allowanceEl, 'ChargeIndicator', 'false');
                $this->addCbcElement($doc, $allowanceEl, 'AllowanceChargeReason', 'Remise');
                $amountEl = $this->addCbcElement($doc, $allowanceEl, 'Amount', $this->formatAmount($allowanceAmount));
                $amountEl->setAttribute('currencyID', $invoice->currency ?? 'EUR');
                $line->appendChild($allowanceEl);
            }

            // Item
            $itemElement = $doc->createElementNS(self::NS_CAC, 'cac:Item');

            // Description (optional)
            if ($item->description) {
                $this->addCbcElement($doc, $itemElement, 'Description', $item->description);
            }

            // Name (use description if no title)
            $itemName = $item->title ?: $item->description ?: 'Service';
            $this->addCbcElement($doc, $itemElement, 'Name', $itemName);

            // ClassifiedTaxCategory
            $taxCategory = $doc->createElementNS(self::NS_CAC, 'cac:ClassifiedTaxCategory');
            $this->addCbcElement($doc, $taxCategory, 'ID', $this->getTaxCategoryCode($item->vat_rate, $invoice));
            $this->addCbcElement($doc, $taxCategory, 'Percent', $item->vat_rate);

            $taxScheme = $doc->createElementNS(self::NS_CAC, 'cac:TaxScheme');
            $this->addCbcElement($doc, $taxScheme, 'ID', 'VAT');
            $taxCategory->appendChild($taxScheme);
            $itemElement->appendChild($taxCategory);

            $line->appendChild($itemElement);

            // Price
            $price = $doc->createElementNS(self::NS_CAC, 'cac:Price');
            $priceAmount = $this->addCbcElement($doc, $price, 'PriceAmount', $this->formatAmount($item->unit_price));
            $priceAmount->setAttribute('currencyID', $invoice->currency ?? 'EUR');
            $line->appendChild($price);

            $root->appendChild($line);
            $lineNumber++;
        }
    }

    /**
     * Add a PostalAddress element.
     */
    protected function addPostalAddress(DOMDocument $doc, DOMElement $parent, array $address): void
    {
        $postalAddress = $doc->createElementNS(self::NS_CAC, 'cac:PostalAddress');

        if (!empty($address['address'])) {
            $this->addCbcElement($doc, $postalAddress, 'StreetName', $address['address']);
        }

        if (!empty($address['city'])) {
            $this->addCbcElement($doc, $postalAddress, 'CityName', $address['city']);
        }

        if (!empty($address['postal_code'])) {
            $this->addCbcElement($doc, $postalAddress, 'PostalZone', $address['postal_code']);
        }

        // Country is mandatory
        $country = $doc->createElementNS(self::NS_CAC, 'cac:Country');
        $this->addCbcElement($doc, $country, 'IdentificationCode', $address['country_code'] ?? 'LU');
        $postalAddress->appendChild($country);

        $parent->appendChild($postalAddress);
    }

    /**
     * Add a PartyTaxScheme element (VAT registration).
     */
    protected function addPartyTaxScheme(DOMDocument $doc, DOMElement $parent, string $vatNumber): void
    {
        $partyTaxScheme = $doc->createElementNS(self::NS_CAC, 'cac:PartyTaxScheme');
        $this->addCbcElement($doc, $partyTaxScheme, 'CompanyID', $vatNumber);

        $taxScheme = $doc->createElementNS(self::NS_CAC, 'cac:TaxScheme');
        $this->addCbcElement($doc, $taxScheme, 'ID', 'VAT');
        $partyTaxScheme->appendChild($taxScheme);

        $parent->appendChild($partyTaxScheme);
    }

    /**
     * Add a CBC element with text content.
     */
    protected function addCbcElement(DOMDocument $doc, DOMElement $parent, string $name, string $value): DOMElement
    {
        $element = $doc->createElementNS(self::NS_CBC, 'cbc:' . $name, htmlspecialchars($value, ENT_XML1));
        $parent->appendChild($element);
        return $element;
    }

    /**
     * Format an amount for XML (2 decimal places).
     */
    protected function formatAmount(float|string|null $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    /**
     * Format a quantity for XML.
     */
    protected function formatQuantity(float|string|null $quantity): string
    {
        $qty = (float) $quantity;
        // Use up to 4 decimal places, but remove trailing zeros
        return rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.');
    }

    /**
     * Get the main tax category code for the invoice.
     */
    protected function getMainTaxCategoryCode(Invoice $invoice): string
    {
        $vatBreakdown = $invoice->vat_breakdown;
        if (empty($vatBreakdown)) {
            return 'S';
        }

        // Get the first (or only) VAT rate
        $firstRate = $vatBreakdown[0]['rate'] ?? 0;
        return $this->getTaxCategoryCode($firstRate, $invoice);
    }

    /**
     * Get the tax category code based on VAT rate and invoice context.
     */
    protected function getTaxCategoryCode(float $rate, Invoice $invoice): string
    {
        if ($rate == 0) {
            // Use the immutable mention KEY (not the user-facing translated text) so this works
            // regardless of the active locale at export time.
            $mentionType = $invoice->effective_vat_mention_type;

            return match ($mentionType) {
                'reverse_charge' => 'AE', // VAT Reverse Charge
                'export' => 'G',          // Export outside EU
                'intra_eu' => 'K',        // Intra-community supply
                'franchise' => 'E',       // Exempt from VAT
                default => 'E',           // Default to exempt for null / 'other'
            };
        }

        return 'S'; // Standard rate
    }

    /**
     * Get endpoint ID for a party.
     * Uses VAT number as the primary identifier.
     */
    protected function getEndpointId(array $party): string
    {
        // If custom Peppol endpoint is set, use it
        if (!empty($party['peppol_endpoint_id'])) {
            return $party['peppol_endpoint_id'];
        }

        // Fallback to VAT number
        return $party['vat_number'] ?? '';
    }

    /**
     * Get endpoint scheme for a party based on country.
     * Maps to correct Peppol participant identifier schemes.
     */
    protected function getEndpointScheme(array $party): string
    {
        // If custom scheme is set and valid, use it
        if (!empty($party['peppol_endpoint_scheme'])) {
            $scheme = $party['peppol_endpoint_scheme'];
            // Convert old/incorrect scheme codes to correct ones
            return $this->normalizeSchemeCode($scheme, $party['country_code'] ?? 'LU');
        }

        // Default scheme based on country
        $countryCode = $party['country_code'] ?? 'LU';
        return $this->getDefaultSchemeForCountry($countryCode);
    }

    /**
     * Normalize scheme code to correct Peppol identifier.
     */
    protected function normalizeSchemeCode(string $scheme, string $countryCode): string
    {
        // Répare deux codes hérités qui n'ont jamais désigné le Luxembourg.
        // 0184 est danois (DK:DIGST) et 9934 est croate (HR:VAT) ; l'interface
        // a longtemps proposé le premier sous le libellé « Luxembourg VAT », et
        // le code convertissait vers le second. Les identifiants déjà
        // enregistrés par les utilisateurs sont donc à corriger à la volée.
        // La conversion ne vaut que pour une partie luxembourgeoise : un vrai
        // participant danois ou croate garde son code.
        $schemeMapping = [
            '0184' => match ($countryCode) {
                'LU' => self::SCHEME_LU_VAT,
                default => '0184',
            },
            '9934' => match ($countryCode) {
                'LU' => self::SCHEME_LU_VAT,
                default => '9934',
            },
        ];

        return $schemeMapping[$scheme] ?? $scheme;
    }

    /**
     * Get default Peppol scheme for a country.
     */
    protected function getDefaultSchemeForCountry(string $countryCode): string
    {
        return match($countryCode) {
            'LU' => self::SCHEME_LU_VAT,
            'BE' => '0208',  // BE:CBE
            'FR' => '0009',  // FR:SIRET
            'DE' => '9930',  // DE:VAT
            'NL' => '0106',  // NL:KVK
            'AT' => '9914',  // AT:VAT
            'IT' => '0211',  // IT:IVA
            'ES' => '9920',  // ES:VAT
            'DK' => '0184',  // DK:DIGST
            'SE' => '0007',  // SE:ORGNR
            'NO' => '9908',  // NO:ORGNR
            'FI' => '0037',  // FI:OVT
            'PT' => '9946',  // PT:VAT
            'IE' => '9928',  // IE:VAT
            'PL' => '9945',  // PL:VAT
            'CZ' => '9922',  // CZ:VAT
            'GB' => '9932',  // GB:VAT (post-Brexit)
            'CH' => '9927',  // CH:VAT
            default => self::SCHEME_LU_VAT,
        };
    }

    /**
     * Map internal unit codes to UN/ECE Recommendation 20 codes.
     * Only codes from UN/ECE Rec 20 that are accepted by Peppol BIS 3.0.
     */
    protected function mapUnitCode(?string $unit): string
    {
        // UN/ECE Rec 20 codes accepted by Peppol
        return match (strtolower($unit ?? '')) {
            'hour', 'heure', 'h' => 'HUR',      // Hour
            'day', 'jour', 'j' => 'DAY',        // Day
            'piece', 'unit', 'pièce', 'unité', 'u', '', 'each', 'ea' => 'C62',  // One (unit)
            'package', 'paquet', 'pack', 'pk' => 'XPK',  // Package (Rec 21)
            'month', 'mois' => 'MON',           // Month
            'year', 'an', 'année' => 'ANN',     // Year
            'week', 'semaine' => 'WEE',         // Week
            'kg', 'kilogram', 'kilogramme' => 'KGM',  // Kilogram
            'g', 'gram', 'gramme' => 'GRM',     // Gram
            'm', 'meter', 'mètre' => 'MTR',     // Meter
            'km', 'kilometer', 'kilomètre' => 'KMT',  // Kilometer
            'l', 'litre', 'liter' => 'LTR',     // Litre
            'set', 'ensemble' => 'C62',         // Set -> use unit
            'box', 'boîte' => 'XBX',            // Box (Rec 21)
            'pair', 'paire' => 'PR',            // Pair
            default => 'C62',                    // Default to unit (C62 is universally accepted)
        };
    }

    /**
     * Get the filename for the export.
     */
    public function getFilename(Invoice $invoice): string
    {
        $type = $invoice->isCreditNote() ? 'creditnote' : 'invoice';
        return "peppol_{$type}_{$invoice->number}.xml";
    }
}
