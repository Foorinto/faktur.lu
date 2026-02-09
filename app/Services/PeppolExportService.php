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

        // Add document content
        $this->addDocumentHeader($doc, $root, $invoice, $isCreditNote);
        $this->addAccountingSupplierParty($doc, $root, $invoice);
        $this->addAccountingCustomerParty($doc, $root, $invoice);
        $this->addPaymentMeans($doc, $root, $invoice);
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

        // DueDate (optional but recommended)
        if ($invoice->due_at) {
            $this->addCbcElement($doc, $root, 'DueDate', $invoice->due_at->format('Y-m-d'));
        }

        // InvoiceTypeCode or CreditNoteTypeCode
        $typeCode = $isCreditNote ? self::TYPE_CREDIT_NOTE : self::TYPE_INVOICE;
        $typeElement = $isCreditNote ? 'CreditNoteTypeCode' : 'InvoiceTypeCode';
        $this->addCbcElement($doc, $root, $typeElement, $typeCode);

        // DocumentCurrencyCode
        $this->addCbcElement($doc, $root, 'DocumentCurrencyCode', $invoice->currency ?? 'EUR');

        // BuyerReference (optional - could use PO number or client reference)
        if ($invoice->payment_reference) {
            $this->addCbcElement($doc, $root, 'BuyerReference', $invoice->payment_reference);
        }

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

        // EndpointID (Peppol ID)
        if (!empty($seller['peppol_endpoint_id']) && !empty($seller['peppol_endpoint_scheme'])) {
            $endpoint = $this->addCbcElement($doc, $party, 'EndpointID', $seller['peppol_endpoint_id']);
            $endpoint->setAttribute('schemeID', $seller['peppol_endpoint_scheme']);
        }

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

        // EndpointID (Peppol ID)
        if (!empty($buyer['peppol_endpoint_id']) && !empty($buyer['peppol_endpoint_scheme'])) {
            $endpoint = $this->addCbcElement($doc, $party, 'EndpointID', $buyer['peppol_endpoint_id']);
            $endpoint->setAttribute('schemeID', $buyer['peppol_endpoint_scheme']);
        }

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
            $this->addCbcElement($doc, $taxCategory, 'ID', $this->getTaxCategoryCode($rate, $invoice));
            $this->addCbcElement($doc, $taxCategory, 'Percent', $rate);

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
     * Add LegalMonetaryTotal.
     */
    protected function addLegalMonetaryTotal(DOMDocument $doc, DOMElement $root, Invoice $invoice, bool $isCreditNote): void
    {
        $monetary = $doc->createElementNS(self::NS_CAC, 'cac:LegalMonetaryTotal');
        $currency = $invoice->currency ?? 'EUR';

        // LineExtensionAmount (sum of line totals HT)
        $lineExt = $this->addCbcElement($doc, $monetary, 'LineExtensionAmount', $this->formatAmount($invoice->total_ht));
        $lineExt->setAttribute('currencyID', $currency);

        // TaxExclusiveAmount (total HT)
        $taxExcl = $this->addCbcElement($doc, $monetary, 'TaxExclusiveAmount', $this->formatAmount($invoice->total_ht));
        $taxExcl->setAttribute('currencyID', $currency);

        // TaxInclusiveAmount (total TTC)
        $taxIncl = $this->addCbcElement($doc, $monetary, 'TaxInclusiveAmount', $this->formatAmount($invoice->total_ttc));
        $taxIncl->setAttribute('currencyID', $currency);

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

            // Item
            $itemElement = $doc->createElementNS(self::NS_CAC, 'cac:Item');

            // Description (optional)
            if ($item->description) {
                $this->addCbcElement($doc, $itemElement, 'Description', $item->description);
            }

            // Name
            $this->addCbcElement($doc, $itemElement, 'Name', $item->title);

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
     * Get the tax category code based on VAT rate and invoice context.
     */
    protected function getTaxCategoryCode(float $rate, Invoice $invoice): string
    {
        if ($rate == 0) {
            // Determine exemption reason
            $vatMention = $invoice->vat_mention ?? $invoice->effective_vat_mention ?? '';

            if (str_contains($vatMention, 'reverse_charge') || str_contains($vatMention, 'autoliquidation')) {
                return 'AE'; // VAT Reverse Charge
            }

            if (str_contains($vatMention, 'export')) {
                return 'G'; // Export outside EU
            }

            if (str_contains($vatMention, 'intra') || str_contains($vatMention, 'intracommunautaire')) {
                return 'K'; // Intra-community supply
            }

            if (str_contains($vatMention, 'franchise')) {
                return 'E'; // Exempt from VAT
            }

            return 'E'; // Default to exempt
        }

        return 'S'; // Standard rate
    }

    /**
     * Map internal unit codes to UN/ECE Recommendation 20 codes.
     */
    protected function mapUnitCode(?string $unit): string
    {
        return match ($unit) {
            'hour' => 'HUR',
            'day' => 'DAY',
            'piece', 'unit' => 'C62', // One (unit)
            'package' => 'PK',
            'month' => 'MON',
            'word' => 'C62',
            'page' => 'C62',
            default => 'C62', // Default to unit
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
