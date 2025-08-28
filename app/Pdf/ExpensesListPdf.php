<?php

namespace App\Pdf;

use TCPDF;

class ExpensesListPdf extends TCPDF
{
    protected $expenses;
    protected $filters = [];
    protected $settings = [];
    protected $font = 'arial';

    public function setExpenses($expenses) { $this->expenses = $expenses; }
    public function setFilters(array $filters) { $this->filters = $filters; }
    public function setSettings(array $settings) { $this->settings = $settings; }

    public function Header()
    {
        $this->SetFont($this->font, 'B', 16);
        $this->Cell(0, 8, ($this->settings['company_name'] ?? 'Company') . ' - Expenses Report', 0, 1, 'C');
        $this->SetFont($this->font, '', 10);
        $this->Cell(0, 6, $this->settings['company_address'] ?? '', 0, 1, 'C');
        $this->SetFont($this->font, 'B', 12);
        $this->Cell(0, 6, 'Expenses List', 0, 1, 'C');

        // Filters
        $filters = [];
        if (!empty($this->filters['date_from'])) $filters[] = 'From: ' . $this->filters['date_from'];
        if (!empty($this->filters['date_to'])) $filters[] = 'To: ' . $this->filters['date_to'];
        if (!empty($this->filters['search'])) $filters[] = 'Search: ' . $this->filters['search'];
        if (!empty($filters)) {
            $this->SetFont($this->font, '', 9);
            $this->SetTextColor(128, 128, 128);
            $this->Cell(0, 4, 'Filters: ' . implode(' | ', $filters), 0, 1, 'C');
            $this->SetTextColor(0, 0, 0);
        }
        // $this->setHeaderMargin(10);
    }

    public function generate(): string
    {
        $this->AddPage('L');
        // Increase space after header by 40 units
        $this->SetY($this->GetY() + 40);
        $this->SetFont($this->font, 'B', 11);
        $this->SetDrawColor(0, 0, 0);

        // Columns
        $w = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $col = [
            'id' => $w * 0.06,
            'name' => $w * 0.18,
            'category' => $w * 0.16,
            'amount' => $w * 0.12,
            'method' => $w * 0.12,
            'date' => $w * 0.14,
            'user' => $w * 0.12,
            'desc' => $w * 0.10,
        ];

        $this->SetHeaderMargin(50);

        // Header row
        $this->Cell($col['id'], 6, 'ID', 'TB', 0, 'C');
        $this->Cell($col['name'], 6, 'Name', 'TB', 0, 'C');
        $this->Cell($col['category'], 6, 'Category', 'TB', 0, 'C');
        $this->Cell($col['amount'], 6, 'Amount', 'TB', 0, 'C');
        $this->Cell($col['method'], 6, 'Method', 'TB', 0, 'C');
        $this->Cell($col['date'], 6, 'Date', 'TB', 0, 'C');
        $this->Cell($col['user'], 6, 'User', 'TB', 0, 'C');
        $this->Cell($col['desc'], 6, 'Description', 'TB', 1, 'C');

        $this->SetFont($this->font, '', 10);
        $fill = false;
        $total = 0.0;

        foreach ($this->expenses as $e) {
            if ($this->GetY() > 180) {
                $this->AddPage('L');
                // Maintain increased header margin on new pages
                $this->SetY($this->GetY() + 40);
                $this->SetFont($this->font, 'B', 11);
                $this->Cell($col['id'], 6, 'ID', 0, 0, 'C');
                $this->Cell($col['name'], 6, 'Name', 0, 0, 'C');
                $this->Cell($col['category'], 6, 'Category', 0, 0, 'C');
                $this->Cell($col['amount'], 6, 'Amount', 0, 0, 'C');
                $this->Cell($col['method'], 6, 'Method', 0, 0, 'C');
                $this->Cell($col['date'], 6, 'Date', 0, 0, 'C');
                $this->Cell($col['user'], 6, 'User', 0, 0, 'C');
                $this->Cell($col['desc'], 6, 'Description', 0, 1, 'C');
                $this->SetFont($this->font, '', 10);
            }

            $this->SetFillColor($fill ? 248 : 255, $fill ? 249 : 255, $fill ? 250 : 255);
            $categoryName = $e->category_name ?? ($e->category->name ?? null) ?? '-';
            $this->Cell($col['id'], 6, $e->id, 0, 0, 'C', $fill);
            $this->Cell($col['name'], 6, $e->name, 0, 0, 'L', $fill);
            $this->Cell($col['category'], 6, $categoryName, 0, 0, 'L', $fill);
            $this->Cell($col['amount'], 6, number_format((float)$e->amount, 3) . ' ' . ($this->settings['currency_symbol'] ?? ''), 0, 0, 'R', $fill);
            $this->Cell($col['method'], 6, $e->payment_method ?? '-', 0, 0, 'C', $fill);
            $this->Cell($col['date'], 6, $e->expense_date ?? '-', 0, 0, 'C', $fill);
            $this->Cell($col['user'], 6, $e->user->name ?? '-', 0, 0, 'L', $fill);
            $this->Cell($col['desc'], 6, $this->truncate($e->description ?? '', 40), 0, 1, 'L', $fill);
            $fill = !$fill;
            $total += (float)$e->amount;
        }

        // Totals row
        $this->SetFont($this->font, 'B', 10);
        $this->SetFillColor(220, 220, 220);
        $this->Cell($col['id'] + $col['name'] + $col['category'], 6, 'TOTAL', 0, 0, 'R', true);
        $this->Cell($col['amount'], 6, number_format($total, 3) . ' ' . ($this->settings['currency_symbol'] ?? ''), 0, 0, 'R', true);
        $this->Cell($col['method'] + $col['date'] + $col['user'] + $col['desc'], 6, '', 0, 1, 'L', true);

        return $this->Output('', 'S');
    }

    private function truncate(string $text, int $len): string
    {
        return strlen($text) <= $len ? $text : substr($text, 0, $len - 3) . '...';
    }
}


