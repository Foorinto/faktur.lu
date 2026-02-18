<?php

namespace App\Services;

use App\Models\Invoice;
use Atgp\FacturX\Writer;
use Atgp\FacturX\Reader;
use Atgp\FacturX\Utils\ProfileHandler;

class FacturXService
{
    // Factur-X profiles (EN16931 recommended for compliance)
    public const PROFILE_MINIMUM = 'minimum';
    public const PROFILE_BASIC_WL = 'basicwl';
    public const PROFILE_BASIC = 'basic';
    public const PROFILE_EN16931 = 'en16931';
    public const PROFILE_EXTENDED = 'extended';

    // Document type codes (UN/CEFACT)
    protected const TYPE_INVOICE = '380';
    protected const TYPE_CREDIT_NOTE = '381';

    protected InvoicePdfService $pdfService;

    public function __construct(InvoicePdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Generate Factur-X PDF (PDF/A-3 with embedded XML).
     */
    public function generate(Invoice $invoice, string $profile = self::PROFILE_EN16931, ?string $locale = null): string
    {
        // Generate the XML
        $xml = $this->generateXml($invoice, $profile);

        // Generate the PDF
        $pdfContent = $this->pdfService->getContent($invoice, $locale);

        // Embed XML in PDF using the library
        $writer = new Writer();

        return $writer->generate(
            $pdfContent,
            $xml,
            $profile,
            false // Don't validate XSD for now
        );
    }

    /**
     * Generate Factur-X XML (CII format).
     */
    public function generateXml(Invoice $invoice, string $profile = self::PROFILE_EN16931): string
    {
        $isCreditNote = $invoice->isCreditNote();
        $seller = $invoice->seller;
        $buyer = $invoice->buyer;

        // Build the XML structure according to UN/CEFACT Cross Industry Invoice
        $xml = $this->buildXmlStructure($invoice, $profile, $isCreditNote, $seller, $buyer);

        return $xml;
    }

    /**
     * Build the XML structure for Factur-X.
     */
    protected function buildXmlStructure(
        Invoice $invoice,
        string $profile,
        bool $isCreditNote,
        array $seller,
        array $buyer
    ): string {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Root element with namespaces
        $root = $dom->createElementNS(
            'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100',
            'rsm:CrossIndustryInvoice'
        );
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:qdt', 'urn:un:unece:uncefact:data:standard:QualifiedDataType:100');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:udt', 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100');
        $dom->appendChild($root);

        // ExchangedDocumentContext
        $this->addExchangedDocumentContext($dom, $root, $profile);

        // ExchangedDocument
        $this->addExchangedDocument($dom, $root, $invoice, $isCreditNote);

        // SupplyChainTradeTransaction
        $this->addSupplyChainTradeTransaction($dom, $root, $invoice, $seller, $buyer, $isCreditNote);

        return $dom->saveXML();
    }

    /**
     * Add ExchangedDocumentContext element.
     */
    protected function addExchangedDocumentContext(\DOMDocument $dom, \DOMElement $root, string $profile): void
    {
        $context = $dom->createElement('rsm:ExchangedDocumentContext');

        // GuidelineSpecifiedDocumentContextParameter
        $guideline = $dom->createElement('ram:GuidelineSpecifiedDocumentContextParameter');
        $guidelineId = $dom->createElement('ram:ID', $this->getProfileUrn($profile));
        $guideline->appendChild($guidelineId);
        $context->appendChild($guideline);

        $root->appendChild($context);
    }

    /**
     * Add ExchangedDocument element.
     */
    protected function addExchangedDocument(\DOMDocument $dom, \DOMElement $root, Invoice $invoice, bool $isCreditNote): void
    {
        $document = $dom->createElement('rsm:ExchangedDocument');

        // ID (invoice number)
        $id = $dom->createElement('ram:ID', $invoice->number);
        $document->appendChild($id);

        // TypeCode
        $typeCode = $dom->createElement('ram:TypeCode', $isCreditNote ? self::TYPE_CREDIT_NOTE : self::TYPE_INVOICE);
        $document->appendChild($typeCode);

        // IssueDateTime
        $issueDateTime = $dom->createElement('ram:IssueDateTime');
        $dateTimeString = $dom->createElement('udt:DateTimeString', $invoice->issued_at->format('Ymd'));
        $dateTimeString->setAttribute('format', '102');
        $issueDateTime->appendChild($dateTimeString);
        $document->appendChild($issueDateTime);

        // IncludedNote (invoice notes)
        if ($invoice->notes) {
            $note = $dom->createElement('ram:IncludedNote');
            $noteContent = $dom->createElement('ram:Content', $this->escapeXml($invoice->notes));
            $note->appendChild($noteContent);
            $document->appendChild($note);
        }

        $root->appendChild($document);
    }

    /**
     * Add SupplyChainTradeTransaction element.
     */
    protected function addSupplyChainTradeTransaction(
        \DOMDocument $dom,
        \DOMElement $root,
        Invoice $invoice,
        array $seller,
        array $buyer,
        bool $isCreditNote
    ): void {
        $transaction = $dom->createElement('rsm:SupplyChainTradeTransaction');

        // Add line items
        foreach ($invoice->items as $index => $item) {
            $this->addLineItem($dom, $transaction, $item, $index + 1, $invoice->currency ?? 'EUR');
        }

        // ApplicableHeaderTradeAgreement
        $this->addHeaderTradeAgreement($dom, $transaction, $seller, $buyer, $invoice);

        // ApplicableHeaderTradeDelivery
        $this->addHeaderTradeDelivery($dom, $transaction, $invoice);

        // ApplicableHeaderTradeSettlement
        $this->addHeaderTradeSettlement($dom, $transaction, $invoice, $isCreditNote);

        $root->appendChild($transaction);
    }

    /**
     * Add a line item.
     */
    protected function addLineItem(\DOMDocument $dom, \DOMElement $parent, $item, int $lineNumber, string $currency): void
    {
        $lineItem = $dom->createElement('ram:IncludedSupplyChainTradeLineItem');

        // AssociatedDocumentLineDocument
        $lineDoc = $dom->createElement('ram:AssociatedDocumentLineDocument');
        $lineId = $dom->createElement('ram:LineID', (string) $lineNumber);
        $lineDoc->appendChild($lineId);
        $lineItem->appendChild($lineDoc);

        // SpecifiedTradeProduct
        $product = $dom->createElement('ram:SpecifiedTradeProduct');
        $productName = $dom->createElement('ram:Name', $this->escapeXml($item->title ?: $item->description ?: 'Service'));
        $product->appendChild($productName);
        if ($item->description && $item->title) {
            $productDesc = $dom->createElement('ram:Description', $this->escapeXml($item->description));
            $product->appendChild($productDesc);
        }
        $lineItem->appendChild($product);

        // SpecifiedLineTradeAgreement
        $tradeAgreement = $dom->createElement('ram:SpecifiedLineTradeAgreement');
        $netPrice = $dom->createElement('ram:NetPriceProductTradePrice');
        $chargeAmount = $dom->createElement('ram:ChargeAmount', $this->formatAmount($item->unit_price));
        $chargeAmount->setAttribute('currencyID', $currency);
        $netPrice->appendChild($chargeAmount);
        $tradeAgreement->appendChild($netPrice);
        $lineItem->appendChild($tradeAgreement);

        // SpecifiedLineTradeDelivery
        $tradeDelivery = $dom->createElement('ram:SpecifiedLineTradeDelivery');
        $billedQty = $dom->createElement('ram:BilledQuantity', $this->formatQuantity($item->quantity));
        $billedQty->setAttribute('unitCode', $this->mapUnitCode($item->unit));
        $tradeDelivery->appendChild($billedQty);
        $lineItem->appendChild($tradeDelivery);

        // SpecifiedLineTradeSettlement
        $tradeSettlement = $dom->createElement('ram:SpecifiedLineTradeSettlement');

        // ApplicableTradeTax
        $tradeTax = $dom->createElement('ram:ApplicableTradeTax');
        $taxTypeCode = $dom->createElement('ram:TypeCode', 'VAT');
        $tradeTax->appendChild($taxTypeCode);
        $categoryCode = $dom->createElement('ram:CategoryCode', $item->vat_rate > 0 ? 'S' : 'E');
        $tradeTax->appendChild($categoryCode);
        $ratePercent = $dom->createElement('ram:RateApplicablePercent', $this->formatAmount($item->vat_rate));
        $tradeTax->appendChild($ratePercent);
        $tradeSettlement->appendChild($tradeTax);

        // SpecifiedTradeSettlementLineMonetarySummation
        $lineSummation = $dom->createElement('ram:SpecifiedTradeSettlementLineMonetarySummation');
        $lineTotalAmount = $dom->createElement('ram:LineTotalAmount', $this->formatAmount($item->total_ht));
        $lineTotalAmount->setAttribute('currencyID', $currency);
        $lineSummation->appendChild($lineTotalAmount);
        $tradeSettlement->appendChild($lineSummation);

        $lineItem->appendChild($tradeSettlement);
        $parent->appendChild($lineItem);
    }

    /**
     * Add ApplicableHeaderTradeAgreement element.
     */
    protected function addHeaderTradeAgreement(\DOMDocument $dom, \DOMElement $parent, array $seller, array $buyer, Invoice $invoice): void
    {
        $agreement = $dom->createElement('ram:ApplicableHeaderTradeAgreement');

        // BuyerReference
        $buyerRef = $dom->createElement('ram:BuyerReference', $invoice->payment_reference ?: $invoice->number);
        $agreement->appendChild($buyerRef);

        // SellerTradeParty
        $this->addTradeParty($dom, $agreement, 'ram:SellerTradeParty', $seller);

        // BuyerTradeParty
        $this->addTradeParty($dom, $agreement, 'ram:BuyerTradeParty', $buyer);

        // BuyerOrderReferencedDocument (if credit note references original invoice)
        if ($invoice->credit_note_for) {
            $originalInvoice = Invoice::find($invoice->credit_note_for);
            if ($originalInvoice) {
                $orderRef = $dom->createElement('ram:BuyerOrderReferencedDocument');
                $orderRefId = $dom->createElement('ram:IssuerAssignedID', $originalInvoice->number);
                $orderRef->appendChild($orderRefId);
                $agreement->appendChild($orderRef);
            }
        }

        $parent->appendChild($agreement);
    }

    /**
     * Add a TradeParty element (seller or buyer).
     */
    protected function addTradeParty(\DOMDocument $dom, \DOMElement $parent, string $tagName, array $party): void
    {
        $tradeParty = $dom->createElement($tagName);

        // Name
        $name = $party['company_name'] ?? $party['legal_name'] ?? $party['name'] ?? '';
        $nameEl = $dom->createElement('ram:Name', $this->escapeXml($name));
        $tradeParty->appendChild($nameEl);

        // SpecifiedLegalOrganization
        if (!empty($party['matricule']) || !empty($party['rcs_number'])) {
            $legalOrg = $dom->createElement('ram:SpecifiedLegalOrganization');
            $orgId = $dom->createElement('ram:ID', $party['matricule'] ?? $party['rcs_number']);
            $legalOrg->appendChild($orgId);
            $tradeParty->appendChild($legalOrg);
        }

        // PostalTradeAddress
        $address = $dom->createElement('ram:PostalTradeAddress');
        if (!empty($party['postal_code'])) {
            $postalCode = $dom->createElement('ram:PostcodeCode', $party['postal_code']);
            $address->appendChild($postalCode);
        }
        if (!empty($party['address']) || !empty($party['address_line1'])) {
            $line = $dom->createElement('ram:LineOne', $this->escapeXml($party['address'] ?? $party['address_line1']));
            $address->appendChild($line);
        }
        if (!empty($party['city'])) {
            $city = $dom->createElement('ram:CityName', $this->escapeXml($party['city']));
            $address->appendChild($city);
        }
        $countryId = $dom->createElement('ram:CountryID', $party['country_code'] ?? 'LU');
        $address->appendChild($countryId);
        $tradeParty->appendChild($address);

        // URIUniversalCommunication (email)
        if (!empty($party['email'])) {
            $uriComm = $dom->createElement('ram:URIUniversalCommunication');
            $uriId = $dom->createElement('ram:URIID', $party['email']);
            $uriId->setAttribute('schemeID', 'EM');
            $uriComm->appendChild($uriId);
            $tradeParty->appendChild($uriComm);
        }

        // SpecifiedTaxRegistration (VAT number)
        if (!empty($party['vat_number'])) {
            $taxReg = $dom->createElement('ram:SpecifiedTaxRegistration');
            $taxId = $dom->createElement('ram:ID', $party['vat_number']);
            $taxId->setAttribute('schemeID', 'VA');
            $taxReg->appendChild($taxId);
            $tradeParty->appendChild($taxReg);
        }

        $parent->appendChild($tradeParty);
    }

    /**
     * Add ApplicableHeaderTradeDelivery element.
     */
    protected function addHeaderTradeDelivery(\DOMDocument $dom, \DOMElement $parent, Invoice $invoice): void
    {
        $delivery = $dom->createElement('ram:ApplicableHeaderTradeDelivery');

        // ActualDeliverySupplyChainEvent (delivery date = issue date)
        $deliveryEvent = $dom->createElement('ram:ActualDeliverySupplyChainEvent');
        $occurrenceDateTime = $dom->createElement('ram:OccurrenceDateTime');
        $dateTimeString = $dom->createElement('udt:DateTimeString', $invoice->issued_at->format('Ymd'));
        $dateTimeString->setAttribute('format', '102');
        $occurrenceDateTime->appendChild($dateTimeString);
        $deliveryEvent->appendChild($occurrenceDateTime);
        $delivery->appendChild($deliveryEvent);

        $parent->appendChild($delivery);
    }

    /**
     * Add ApplicableHeaderTradeSettlement element.
     */
    protected function addHeaderTradeSettlement(\DOMDocument $dom, \DOMElement $parent, Invoice $invoice, bool $isCreditNote): void
    {
        $settlement = $dom->createElement('ram:ApplicableHeaderTradeSettlement');
        $currency = $invoice->currency ?? 'EUR';

        // InvoiceCurrencyCode
        $currencyCode = $dom->createElement('ram:InvoiceCurrencyCode', $currency);
        $settlement->appendChild($currencyCode);

        // SpecifiedTradeSettlementPaymentMeans
        $seller = $invoice->seller;
        if (!empty($seller['iban'])) {
            $paymentMeans = $dom->createElement('ram:SpecifiedTradeSettlementPaymentMeans');
            $typeCode = $dom->createElement('ram:TypeCode', '30'); // Credit transfer
            $paymentMeans->appendChild($typeCode);

            $payeeAccount = $dom->createElement('ram:PayeePartyCreditorFinancialAccount');
            $ibanEl = $dom->createElement('ram:IBANID', str_replace(' ', '', $seller['iban']));
            $payeeAccount->appendChild($ibanEl);
            $paymentMeans->appendChild($payeeAccount);

            if (!empty($seller['bic'])) {
                $institution = $dom->createElement('ram:PayeeSpecifiedCreditorFinancialInstitution');
                $bicEl = $dom->createElement('ram:BICID', $seller['bic']);
                $institution->appendChild($bicEl);
                $paymentMeans->appendChild($institution);
            }

            $settlement->appendChild($paymentMeans);
        }

        // ApplicableTradeTax (VAT summary)
        foreach ($invoice->vat_breakdown as $vatLine) {
            $tradeTax = $dom->createElement('ram:ApplicableTradeTax');

            $calculatedAmount = $dom->createElement('ram:CalculatedAmount', $this->formatAmount($vatLine['amount']));
            $calculatedAmount->setAttribute('currencyID', $currency);
            $tradeTax->appendChild($calculatedAmount);

            $typeCode = $dom->createElement('ram:TypeCode', 'VAT');
            $tradeTax->appendChild($typeCode);

            $basisAmount = $dom->createElement('ram:BasisAmount', $this->formatAmount($vatLine['base']));
            $basisAmount->setAttribute('currencyID', $currency);
            $tradeTax->appendChild($basisAmount);

            $categoryCode = $dom->createElement('ram:CategoryCode', $vatLine['rate'] > 0 ? 'S' : 'E');
            $tradeTax->appendChild($categoryCode);

            $ratePercent = $dom->createElement('ram:RateApplicablePercent', $this->formatAmount($vatLine['rate']));
            $tradeTax->appendChild($ratePercent);

            $settlement->appendChild($tradeTax);
        }

        // SpecifiedTradePaymentTerms
        if ($invoice->due_at) {
            $paymentTerms = $dom->createElement('ram:SpecifiedTradePaymentTerms');
            $dueDateTime = $dom->createElement('ram:DueDateDateTime');
            $dueDateString = $dom->createElement('udt:DateTimeString', $invoice->due_at->format('Ymd'));
            $dueDateString->setAttribute('format', '102');
            $dueDateTime->appendChild($dueDateString);
            $paymentTerms->appendChild($dueDateTime);
            $settlement->appendChild($paymentTerms);
        }

        // SpecifiedTradeSettlementHeaderMonetarySummation
        $summation = $dom->createElement('ram:SpecifiedTradeSettlementHeaderMonetarySummation');

        $lineTotalAmount = $dom->createElement('ram:LineTotalAmount', $this->formatAmount($invoice->total_ht));
        $lineTotalAmount->setAttribute('currencyID', $currency);
        $summation->appendChild($lineTotalAmount);

        $taxBasisTotalAmount = $dom->createElement('ram:TaxBasisTotalAmount', $this->formatAmount($invoice->total_ht));
        $taxBasisTotalAmount->setAttribute('currencyID', $currency);
        $summation->appendChild($taxBasisTotalAmount);

        $taxTotalAmount = $dom->createElement('ram:TaxTotalAmount', $this->formatAmount($invoice->total_vat));
        $taxTotalAmount->setAttribute('currencyID', $currency);
        $summation->appendChild($taxTotalAmount);

        $grandTotalAmount = $dom->createElement('ram:GrandTotalAmount', $this->formatAmount($invoice->total_ttc));
        $grandTotalAmount->setAttribute('currencyID', $currency);
        $summation->appendChild($grandTotalAmount);

        $duePayableAmount = $dom->createElement('ram:DuePayableAmount', $this->formatAmount($invoice->total_ttc));
        $duePayableAmount->setAttribute('currencyID', $currency);
        $summation->appendChild($duePayableAmount);

        $settlement->appendChild($summation);
        $parent->appendChild($settlement);
    }

    /**
     * Get the URN for a Factur-X profile.
     */
    protected function getProfileUrn(string $profile): string
    {
        return match ($profile) {
            self::PROFILE_MINIMUM => 'urn:factur-x.eu:1p0:minimum',
            self::PROFILE_BASIC_WL => 'urn:factur-x.eu:1p0:basicwl',
            self::PROFILE_BASIC => 'urn:factur-x.eu:1p0:basic',
            self::PROFILE_EN16931 => 'urn:cen.eu:en16931:2017#compliant#urn:factur-x.eu:1p0:en16931',
            self::PROFILE_EXTENDED => 'urn:factur-x.eu:1p0:extended',
            default => 'urn:cen.eu:en16931:2017#compliant#urn:factur-x.eu:1p0:en16931',
        };
    }

    /**
     * Format an amount for XML.
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
        return rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.');
    }

    /**
     * Escape XML special characters.
     */
    protected function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Map unit code to UN/ECE Rec 20.
     */
    protected function mapUnitCode(?string $unit): string
    {
        return match (strtolower($unit ?? '')) {
            'hour', 'heure', 'h' => 'HUR',
            'day', 'jour', 'j' => 'DAY',
            'piece', 'unit', 'pièce', 'unité', 'u', '', 'each', 'ea' => 'C62',
            'month', 'mois' => 'MON',
            'year', 'an', 'année' => 'ANN',
            'kg', 'kilogram', 'kilogramme' => 'KGM',
            'm', 'meter', 'mètre' => 'MTR',
            'l', 'litre', 'liter' => 'LTR',
            default => 'C62',
        };
    }

    /**
     * Get the filename for the export.
     */
    public function getFilename(Invoice $invoice): string
    {
        $type = $invoice->isCreditNote() ? 'avoir' : 'facture';
        return "facturx_{$type}_{$invoice->number}.pdf";
    }

    /**
     * Get the XML filename.
     */
    public function getXmlFilename(Invoice $invoice): string
    {
        return 'factur-x.xml';
    }

    /**
     * Read Factur-X data from an existing PDF.
     */
    public function readFromPdf(string $pdfContent): ?array
    {
        try {
            $reader = new Reader();
            $xml = $reader->extractXML($pdfContent, false);

            if (!$xml) {
                return null;
            }

            // Detect profile from XML
            $doc = new \DOMDocument();
            $doc->loadXML($xml);
            $profile = ProfileHandler::get($doc);

            return [
                'xml' => $xml,
                'profile' => $profile,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
