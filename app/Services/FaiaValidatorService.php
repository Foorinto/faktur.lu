<?php

namespace App\Services;

use DOMDocument;
use Exception;

class FaiaValidatorService
{
    protected array $errors = [];
    protected array $warnings = [];
    protected array $info = [];
    protected ?DOMDocument $dom = null;
    protected ?\DOMXPath $xpath = null;

    /**
     * Validate a FAIA XML file content.
     */
    public function validate(string $xmlContent): array
    {
        $this->errors = [];
        $this->warnings = [];
        $this->info = [];

        // 1. Basic XML validation
        if (!$this->validateXmlStructure($xmlContent)) {
            return $this->getResult();
        }

        // 2. Validate against XSD schema
        $this->validateAgainstSchema($xmlContent);

        // 3. Business validations
        $this->validateHeader();
        $this->validateCompany();
        $this->validateCustomers();
        $this->validateSuppliers();
        $this->validateSalesInvoices();
        $this->validateGeneralLedger();

        return $this->getResult();
    }

    /**
     * Validate XML structure and encoding.
     */
    protected function validateXmlStructure(string $xmlContent): bool
    {
        // Check for empty content
        if (empty(trim($xmlContent))) {
            $this->errors[] = [
                'code' => 'XML_EMPTY',
                'message' => 'Le fichier est vide',
                'category' => 'Structure XML',
            ];
            return false;
        }

        // Check encoding
        if (!mb_check_encoding($xmlContent, 'UTF-8')) {
            $this->warnings[] = [
                'code' => 'XML_ENCODING',
                'message' => 'L\'encodage devrait être UTF-8',
                'category' => 'Structure XML',
            ];
        }

        // Try to load XML
        libxml_use_internal_errors(true);
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;

        if (!$this->dom->loadXML($xmlContent, LIBXML_NONET | LIBXML_NOENT)) {
            $xmlErrors = libxml_get_errors();
            foreach ($xmlErrors as $error) {
                $this->errors[] = [
                    'code' => 'XML_PARSE_ERROR',
                    'message' => trim($error->message),
                    'line' => $error->line,
                    'category' => 'Structure XML',
                ];
            }
            libxml_clear_errors();
            return false;
        }

        libxml_clear_errors();

        // Setup XPath with namespace
        $this->xpath = new \DOMXPath($this->dom);

        // Register namespaces
        $rootNamespace = $this->dom->documentElement->namespaceURI;
        if ($rootNamespace) {
            $this->xpath->registerNamespace('faia', $rootNamespace);
        }

        // Check for FAIA namespace
        $this->validateNamespace($rootNamespace);

        $this->info[] = [
            'code' => 'XML_VALID',
            'message' => 'Structure XML valide',
            'category' => 'Structure XML',
        ];

        return true;
    }

    /**
     * Validate FAIA namespace.
     */
    protected function validateNamespace(?string $namespace): void
    {
        $validNamespaces = [
            'urn:OECD:StandardAuditFile-Taxation/2.00', // Official FAIA 2.01 namespace
            'urn:lu:faia:2.01',
            'urn:lu:aed:FAIA:v1',
            'urn:StandardAuditFile-Taxation-Financial:LU',
        ];

        if (!$namespace) {
            $this->warnings[] = [
                'code' => 'NAMESPACE_MISSING',
                'message' => 'Aucun namespace XML déclaré. Recommandé: urn:lu:faia:2.01',
                'category' => 'Structure XML',
            ];
            return;
        }

        if (!in_array($namespace, $validNamespaces)) {
            $this->warnings[] = [
                'code' => 'NAMESPACE_UNKNOWN',
                'message' => "Namespace non standard: {$namespace}",
                'category' => 'Structure XML',
            ];
        } else {
            $this->info[] = [
                'code' => 'NAMESPACE_VALID',
                'message' => "Namespace FAIA valide: {$namespace}",
                'category' => 'Structure XML',
            ];
        }
    }

    /**
     * Validate against XSD schema.
     */
    protected function validateAgainstSchema(string $xmlContent): void
    {
        $xsdPath = base_path('External/XSD Files/FAIA_v_2.01_full.xsd');

        if (!file_exists($xsdPath)) {
            $this->warnings[] = [
                'code' => 'XSD_NOT_FOUND',
                'message' => 'Schéma XSD non disponible pour validation complète',
                'category' => 'Validation Schema',
            ];
            return;
        }

        libxml_use_internal_errors(true);

        try {
            if (!$this->dom->schemaValidate($xsdPath)) {
                $xsdErrors = libxml_get_errors();
                $errorCount = 0;
                foreach ($xsdErrors as $error) {
                    if ($errorCount >= 10) {
                        $remaining = count($xsdErrors) - 10;
                        $this->warnings[] = [
                            'code' => 'XSD_ERRORS_TRUNCATED',
                            'message' => "... et {$remaining} autres erreurs de schéma",
                            'category' => 'Validation Schema',
                        ];
                        break;
                    }

                    // XSD errors are treated as warnings (not blocking)
                    // because many real-world FAIA files have slight variations
                    $item = [
                        'code' => 'XSD_VALIDATION',
                        'message' => trim($error->message),
                        'line' => $error->line,
                        'category' => 'Validation Schema',
                    ];
                    $this->warnings[] = $item;
                    $errorCount++;
                }
                libxml_clear_errors();
            } else {
                $this->info[] = [
                    'code' => 'XSD_VALID',
                    'message' => 'Conforme au schéma FAIA 2.01',
                    'category' => 'Validation Schema',
                ];
            }
        } catch (Exception $e) {
            $this->warnings[] = [
                'code' => 'XSD_ERROR',
                'message' => 'Impossible de valider contre le schéma XSD',
                'category' => 'Validation Schema',
            ];
        }

        libxml_clear_errors();
    }

    /**
     * Validate FAIA header.
     */
    protected function validateHeader(): void
    {
        $header = $this->getElement('//faia:Header') ?? $this->getElement('//Header');

        if (!$header) {
            $this->errors[] = [
                'code' => 'HEADER_MISSING',
                'message' => 'Élément Header manquant',
                'category' => 'En-tête (Header)',
            ];
            return;
        }

        // AuditFileVersion
        $version = $this->getElementValue($header, 'AuditFileVersion');
        if (!$version) {
            $this->errors[] = [
                'code' => 'VERSION_MISSING',
                'message' => 'AuditFileVersion manquant',
                'category' => 'En-tête (Header)',
            ];
        } else {
            $this->info[] = [
                'code' => 'VERSION_FOUND',
                'message' => "Version FAIA: {$version}",
                'category' => 'En-tête (Header)',
            ];
        }

        // DateCreated
        $dateCreated = $this->getElementValue($header, 'AuditFileDateCreated')
            ?? $this->getElementValue($header, 'DateCreated');
        if (!$dateCreated) {
            $this->warnings[] = [
                'code' => 'DATE_CREATED_MISSING',
                'message' => 'Date de création manquante',
                'category' => 'En-tête (Header)',
            ];
        }

        // Software info
        $softwareName = $this->getElementValue($header, 'SoftwareCompanyName');
        $softwareId = $this->getElementValue($header, 'SoftwareID');

        if (!$softwareName && !$softwareId) {
            $this->warnings[] = [
                'code' => 'SOFTWARE_INFO_MISSING',
                'message' => 'Informations sur le logiciel manquantes',
                'category' => 'En-tête (Header)',
            ];
        }

        // Fiscal year / Period
        $this->validatePeriod($header);
    }

    /**
     * Validate fiscal period.
     */
    protected function validatePeriod($header): void
    {
        $startDate = $this->getElementValue($header, 'SelectionCriteria/PeriodStart')
            ?? $this->getElementValue($header, 'FiscalYear');
        $endDate = $this->getElementValue($header, 'SelectionCriteria/PeriodEnd');

        if ($startDate && $endDate) {
            $start = strtotime($startDate);
            $end = strtotime($endDate);

            if ($start && $end && $start > $end) {
                $this->errors[] = [
                    'code' => 'PERIOD_INVALID',
                    'message' => 'La date de début est postérieure à la date de fin',
                    'category' => 'En-tête (Header)',
                ];
            } else {
                $this->info[] = [
                    'code' => 'PERIOD_VALID',
                    'message' => "Période: {$startDate} à {$endDate}",
                    'category' => 'En-tête (Header)',
                ];
            }
        }
    }

    /**
     * Validate company information.
     */
    protected function validateCompany(): void
    {
        $company = $this->getElement('//faia:Header/faia:Company')
            ?? $this->getElement('//Header/Company');

        if (!$company) {
            $this->errors[] = [
                'code' => 'COMPANY_MISSING',
                'message' => 'Informations société (Company) manquantes',
                'category' => 'Société émettrice',
            ];
            return;
        }

        // Company name
        $companyName = $this->getElementValue($company, 'CompanyName')
            ?? $this->getElementValue($company, 'Name');
        if (!$companyName) {
            $this->errors[] = [
                'code' => 'COMPANY_NAME_MISSING',
                'message' => 'Nom de la société manquant',
                'category' => 'Société émettrice',
            ];
        }

        // VAT number - check multiple possible locations
        $vatNumber = $this->getElementValue($company, 'TaxRegistrationNumber')
            ?? $this->getElementValue($company, 'TaxRegistration/TaxRegistrationNumber')
            ?? $this->getElementValue($company, 'VATNumber');

        if ($vatNumber) {
            $this->validateVatNumber($vatNumber, 'Société émettrice');
        } else {
            $this->warnings[] = [
                'code' => 'COMPANY_VAT_MISSING',
                'message' => 'Numéro TVA de la société non renseigné',
                'category' => 'Société émettrice',
            ];
        }

        // Registration number (matricule)
        $regNumber = $this->getElementValue($company, 'RegistrationNumber')
            ?? $this->getElementValue($company, 'CompanyID');

        if (!$regNumber) {
            $this->warnings[] = [
                'code' => 'COMPANY_MATRICULE_MISSING',
                'message' => 'Matricule/numéro d\'enregistrement non renseigné',
                'category' => 'Société émettrice',
            ];
        }
    }

    /**
     * Validate VAT number format.
     */
    protected function validateVatNumber(string $vatNumber, string $category): bool
    {
        $vatNumber = strtoupper(preg_replace('/\s+/', '', $vatNumber));

        // Luxembourg VAT format: LU + 8 digits
        if (preg_match('/^LU\d{8}$/', $vatNumber)) {
            $this->info[] = [
                'code' => 'VAT_VALID_LU',
                'message' => "N° TVA Luxembourg valide: {$vatNumber}",
                'category' => $category,
            ];
            return true;
        }

        // EU VAT format (2 letters + alphanumeric)
        if (preg_match('/^[A-Z]{2}[A-Z0-9]{2,12}$/', $vatNumber)) {
            $this->info[] = [
                'code' => 'VAT_VALID_EU',
                'message' => "N° TVA UE détecté: {$vatNumber}",
                'category' => $category,
            ];
            return true;
        }

        $this->warnings[] = [
            'code' => 'VAT_FORMAT_INVALID',
            'message' => "Format N° TVA non reconnu: {$vatNumber}",
            'category' => $category,
        ];
        return false;
    }

    /**
     * Validate customers.
     */
    protected function validateCustomers(): void
    {
        $customers = $this->getElements('//faia:MasterFiles/faia:Customers/faia:Customer')
            ?? $this->getElements('//MasterFiles/Customers/Customer')
            ?? $this->getElements('//Customers/Customer');

        if (!$customers || $customers->length === 0) {
            // Customers might be embedded in invoices, this is not always an error
            return;
        }

        $customerIds = [];
        $duplicates = [];

        foreach ($customers as $customer) {
            $customerId = $this->getElementValue($customer, 'CustomerID');

            if (!$customerId) {
                $this->errors[] = [
                    'code' => 'CUSTOMER_ID_MISSING',
                    'message' => 'CustomerID manquant pour un client',
                    'category' => 'Clients (Customers)',
                ];
                continue;
            }

            // Check for duplicates
            if (in_array($customerId, $customerIds)) {
                $duplicates[] = $customerId;
            } else {
                $customerIds[] = $customerId;
            }

            // Check name
            $name = $this->getElementValue($customer, 'CustomerName')
                ?? $this->getElementValue($customer, 'Name');
            if (!$name) {
                $this->warnings[] = [
                    'code' => 'CUSTOMER_NAME_MISSING',
                    'message' => "Nom manquant pour client {$customerId}",
                    'category' => 'Clients (Customers)',
                ];
            }

            // Validate VAT if present
            $vatNumber = $this->getElementValue($customer, 'TaxRegistrationNumber')
                ?? $this->getElementValue($customer, 'VATNumber');
            if ($vatNumber) {
                $this->validateVatNumber($vatNumber, 'Clients (Customers)');
            }
        }

        if (!empty($duplicates)) {
            $this->errors[] = [
                'code' => 'CUSTOMER_DUPLICATE',
                'message' => 'CustomerID en double: ' . implode(', ', array_unique($duplicates)),
                'category' => 'Clients (Customers)',
            ];
        }

        $this->info[] = [
            'code' => 'CUSTOMERS_COUNT',
            'message' => count($customerIds) . ' client(s) trouvé(s)',
            'category' => 'Clients (Customers)',
        ];
    }

    /**
     * Validate suppliers.
     */
    protected function validateSuppliers(): void
    {
        $suppliers = $this->getElements('//faia:MasterFiles/faia:Suppliers/faia:Supplier')
            ?? $this->getElements('//MasterFiles/Suppliers/Supplier')
            ?? $this->getElements('//Suppliers/Supplier');

        if (!$suppliers || $suppliers->length === 0) {
            return;
        }

        $supplierIds = [];

        foreach ($suppliers as $supplier) {
            $supplierId = $this->getElementValue($supplier, 'SupplierID');

            if (!$supplierId) {
                $this->errors[] = [
                    'code' => 'SUPPLIER_ID_MISSING',
                    'message' => 'SupplierID manquant pour un fournisseur',
                    'category' => 'Fournisseurs (Suppliers)',
                ];
                continue;
            }

            if (in_array($supplierId, $supplierIds)) {
                $this->errors[] = [
                    'code' => 'SUPPLIER_DUPLICATE',
                    'message' => "SupplierID en double: {$supplierId}",
                    'category' => 'Fournisseurs (Suppliers)',
                ];
            } else {
                $supplierIds[] = $supplierId;
            }
        }

        if (!empty($supplierIds)) {
            $this->info[] = [
                'code' => 'SUPPLIERS_COUNT',
                'message' => count($supplierIds) . ' fournisseur(s) trouvé(s)',
                'category' => 'Fournisseurs (Suppliers)',
            ];
        }
    }

    /**
     * Validate sales invoices.
     */
    protected function validateSalesInvoices(): void
    {
        $invoices = $this->getElements('//faia:SourceDocuments/faia:SalesInvoices/faia:Invoice')
            ?? $this->getElements('//SalesInvoices/Invoice')
            ?? $this->getElements('//Invoices/Invoice');

        if (!$invoices || $invoices->length === 0) {
            $this->warnings[] = [
                'code' => 'NO_INVOICES',
                'message' => 'Aucune facture trouvée dans le fichier',
                'category' => 'Factures (SalesInvoices)',
            ];
            return;
        }

        $invoiceNumbers = [];
        $totalHt = 0;
        $totalVat = 0;
        $totalTtc = 0;
        $issues = 0;

        foreach ($invoices as $invoice) {
            $invoiceNo = $this->getElementValue($invoice, 'InvoiceNo')
                ?? $this->getElementValue($invoice, 'InvoiceNumber');

            if (!$invoiceNo) {
                $this->errors[] = [
                    'code' => 'INVOICE_NO_MISSING',
                    'message' => 'Numéro de facture manquant',
                    'category' => 'Factures (SalesInvoices)',
                ];
                $issues++;
                continue;
            }

            // Check for duplicates
            if (in_array($invoiceNo, $invoiceNumbers)) {
                $this->errors[] = [
                    'code' => 'INVOICE_DUPLICATE',
                    'message' => "Numéro de facture en double: {$invoiceNo}",
                    'category' => 'Factures (SalesInvoices)',
                ];
                $issues++;
            } else {
                $invoiceNumbers[] = $invoiceNo;
            }

            // Validate date
            $invoiceDate = $this->getElementValue($invoice, 'InvoiceDate');
            if (!$invoiceDate) {
                $this->errors[] = [
                    'code' => 'INVOICE_DATE_MISSING',
                    'message' => "Date manquante pour facture {$invoiceNo}",
                    'category' => 'Factures (SalesInvoices)',
                ];
                $issues++;
            } elseif (!strtotime($invoiceDate)) {
                $this->errors[] = [
                    'code' => 'INVOICE_DATE_INVALID',
                    'message' => "Date invalide pour facture {$invoiceNo}: {$invoiceDate}",
                    'category' => 'Factures (SalesInvoices)',
                ];
                $issues++;
            }

            // Validate amounts - check multiple possible locations
            $netTotal = (float) ($this->getElementValue($invoice, 'NetTotal')
                ?? $this->getElementValue($invoice, 'DocumentTotals/NetTotal') ?? 0);

            // Tax total can be in various places
            $taxTotal = (float) ($this->getElementValue($invoice, 'TaxTotal')
                ?? $this->getElementValue($invoice, 'DocumentTotals/TaxTotal')
                ?? $this->getElementValue($invoice, 'DocumentTotals/TaxInformationTotals/TaxAmount/Amount')
                ?? $this->calculateTaxFromGrossNet($invoice, $netTotal)
                ?? 0);

            $grossTotal = (float) ($this->getElementValue($invoice, 'GrossTotal')
                ?? $this->getElementValue($invoice, 'DocumentTotals/GrossTotal') ?? 0);

            $totalHt += $netTotal;
            $totalVat += $taxTotal;
            $totalTtc += $grossTotal;

            // Validate totals coherence (only if we have explicit tax total)
            $explicitTaxTotal = $this->getElementValue($invoice, 'TaxTotal')
                ?? $this->getElementValue($invoice, 'DocumentTotals/TaxTotal');

            if ($explicitTaxTotal !== null) {
                $calculatedGross = round($netTotal + (float)$explicitTaxTotal, 2);
                if ($grossTotal > 0 && abs($grossTotal - $calculatedGross) > 0.01) {
                    $this->warnings[] = [
                        'code' => 'INVOICE_TOTAL_MISMATCH',
                        'message' => "Incohérence de totaux pour {$invoiceNo}: HT({$netTotal}) + TVA({$explicitTaxTotal}) ≠ TTC({$grossTotal})",
                        'category' => 'Factures (SalesInvoices)',
                    ];
                }
            }

            // Customer reference - check multiple possible locations
            $customerId = $this->getElementValue($invoice, 'CustomerID')
                ?? $this->getElementValue($invoice, 'Customer/CustomerID')
                ?? $this->getElementValue($invoice, 'CustomerInfo/CustomerID');
            if (!$customerId) {
                // Also check if there's a Name instead of CustomerID
                $customerName = $this->getElementValue($invoice, 'CustomerInfo/Name')
                    ?? $this->getElementValue($invoice, 'Customer/Name');
                if (!$customerName) {
                    $this->warnings[] = [
                        'code' => 'INVOICE_CUSTOMER_MISSING',
                        'message' => "Référence client manquante pour facture {$invoiceNo}",
                        'category' => 'Factures (SalesInvoices)',
                    ];
                }
            }
        }

        // Check sequence
        $this->validateInvoiceSequence($invoiceNumbers);

        // Summary
        $this->info[] = [
            'code' => 'INVOICES_SUMMARY',
            'message' => sprintf(
                '%d facture(s) - Total HT: %.2f€ - TVA: %.2f€ - TTC: %.2f€',
                count($invoiceNumbers),
                $totalHt,
                $totalVat,
                $totalTtc
            ),
            'category' => 'Factures (SalesInvoices)',
        ];

        if ($issues === 0) {
            $this->info[] = [
                'code' => 'INVOICES_VALID',
                'message' => 'Toutes les factures sont valides',
                'category' => 'Factures (SalesInvoices)',
            ];
        }
    }

    /**
     * Calculate tax from gross and net totals when explicit tax is not available.
     */
    protected function calculateTaxFromGrossNet($invoice, float $netTotal): ?float
    {
        $grossTotal = (float) ($this->getElementValue($invoice, 'GrossTotal')
            ?? $this->getElementValue($invoice, 'DocumentTotals/GrossTotal') ?? 0);

        if ($grossTotal > 0 && $netTotal > 0) {
            return round($grossTotal - $netTotal, 2);
        }

        return null;
    }

    /**
     * Validate invoice number sequence.
     */
    protected function validateInvoiceSequence(array $invoiceNumbers): void
    {
        if (count($invoiceNumbers) < 2) {
            return;
        }

        // Group by prefix and year
        $grouped = [];
        foreach ($invoiceNumbers as $number) {
            if (preg_match('/^([A-Z]*)-?(\d{4})-(\d+)$/i', $number, $matches)) {
                $key = ($matches[1] ?: 'DEFAULT') . '-' . $matches[2];
                $grouped[$key][] = (int) $matches[3];
            }
        }

        $gaps = [];
        foreach ($grouped as $key => $numbers) {
            sort($numbers);
            $expected = $numbers[0];

            foreach ($numbers as $num) {
                if ($num !== $expected) {
                    for ($i = $expected; $i < $num; $i++) {
                        $gaps[] = "{$key}-" . str_pad($i, 3, '0', STR_PAD_LEFT);
                    }
                }
                $expected = $num + 1;
            }
        }

        if (!empty($gaps)) {
            $gapList = count($gaps) > 5
                ? implode(', ', array_slice($gaps, 0, 5)) . '...'
                : implode(', ', $gaps);

            $this->errors[] = [
                'code' => 'SEQUENCE_GAP',
                'message' => 'Rupture de séquence détectée. Numéros manquants: ' . $gapList,
                'category' => 'Factures (SalesInvoices)',
            ];
        } else {
            $this->info[] = [
                'code' => 'SEQUENCE_VALID',
                'message' => 'Numérotation séquentielle valide',
                'category' => 'Factures (SalesInvoices)',
            ];
        }
    }

    /**
     * Validate general ledger entries.
     */
    protected function validateGeneralLedger(): void
    {
        $entries = $this->getElements('//faia:GeneralLedgerEntries/faia:Journal/faia:Transaction')
            ?? $this->getElements('//GeneralLedgerEntries/Journal/Transaction')
            ?? $this->getElements('//GeneralLedgerEntries/Transaction');

        if (!$entries || $entries->length === 0) {
            // General ledger entries are optional in some FAIA versions
            return;
        }

        $imbalanced = 0;

        foreach ($entries as $entry) {
            $debitTotal = 0;
            $creditTotal = 0;

            $lines = $entry->getElementsByTagName('Line');
            foreach ($lines as $line) {
                $debit = (float) ($this->getElementValue($line, 'DebitAmount') ?? 0);
                $credit = (float) ($this->getElementValue($line, 'CreditAmount') ?? 0);
                $debitTotal += $debit;
                $creditTotal += $credit;
            }

            if (abs($debitTotal - $creditTotal) > 0.01) {
                $imbalanced++;
            }
        }

        if ($imbalanced > 0) {
            $this->errors[] = [
                'code' => 'LEDGER_IMBALANCED',
                'message' => "{$imbalanced} écriture(s) comptable(s) non équilibrée(s) (débit ≠ crédit)",
                'category' => 'Écritures (GeneralLedger)',
            ];
        } else {
            $this->info[] = [
                'code' => 'LEDGER_BALANCED',
                'message' => 'Toutes les écritures sont équilibrées',
                'category' => 'Écritures (GeneralLedger)',
            ];
        }
    }

    /**
     * Get a single element by XPath.
     */
    protected function getElement(string $xpath, $context = null)
    {
        if (!$this->xpath) {
            return null;
        }

        $result = $this->xpath->query($xpath, $context);
        return $result && $result->length > 0 ? $result->item(0) : null;
    }

    /**
     * Get multiple elements by XPath.
     */
    protected function getElements(string $xpath, $context = null): ?\DOMNodeList
    {
        if (!$this->xpath) {
            return null;
        }

        $result = $this->xpath->query($xpath, $context);
        return $result && $result->length > 0 ? $result : null;
    }

    /**
     * Get element text value.
     */
    protected function getElementValue($context, string $path): ?string
    {
        if (!$context) {
            return null;
        }

        // Try with namespace
        $element = $this->xpath->query("faia:{$path}", $context)->item(0)
            ?? $this->xpath->query($path, $context)->item(0);

        if ($element) {
            return trim($element->textContent);
        }

        // Try direct child element
        $parts = explode('/', $path);
        $current = $context;

        foreach ($parts as $part) {
            $found = false;
            foreach ($current->childNodes as $child) {
                if ($child->nodeName === $part || $child->localName === $part) {
                    $current = $child;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return null;
            }
        }

        return trim($current->textContent);
    }

    /**
     * Get validation result.
     */
    protected function getResult(): array
    {
        $errorCount = count($this->errors);
        $warningCount = count($this->warnings);

        // Calculate score
        $score = 100;
        $score -= $errorCount * 15; // -15 points per error
        $score -= $warningCount * 5; // -5 points per warning
        $score = max(0, min(100, $score));

        // Determine status
        if ($errorCount === 0 && $warningCount === 0) {
            $status = 'valid';
            $statusMessage = 'Fichier FAIA valide et conforme';
        } elseif ($errorCount === 0) {
            $status = 'valid_with_warnings';
            $statusMessage = 'Fichier valide avec avertissements';
        } else {
            $status = 'invalid';
            $statusMessage = 'Fichier non conforme';
        }

        return [
            'status' => $status,
            'statusMessage' => $statusMessage,
            'score' => $score,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'info' => $this->info,
            'summary' => [
                'errors' => $errorCount,
                'warnings' => $warningCount,
                'info' => count($this->info),
            ],
        ];
    }
}
