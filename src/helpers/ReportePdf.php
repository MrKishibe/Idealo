<?php

namespace Idealo\Helpers;

class ReportePdf extends \TCPDF
{
    private string $reportTitle = '';
    private string $reportSubtitle = '';
    private string $generatedAt = '';
    private string $generatedBy = '';
    private bool $compactLayout = false;

    public function setReportDetails(string $title, string $subtitle = ''): void
    {
        $this->reportTitle = $title;
        $this->reportSubtitle = $subtitle;
        $this->generatedAt = date('d/m/Y H:i:s');
        $this->generatedBy = (string) ($_SESSION['nombre_usuario'] ?? 'Usuario no identificado');
        $this->compactLayout = false;

        $this->SetMargins(12, 36, 12);
        $this->SetHeaderMargin(7);
        $this->SetFooterMargin(10);
        $this->SetAutoPageBreak(true, 15);
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);
    }

    public function setCompactReportDetails(string $title, string $subtitle = ''): void
    {
        $this->reportTitle = $title;
        $this->reportSubtitle = $subtitle;
        $this->generatedAt = date('d/m/Y');
        $this->generatedBy = (string) (
            $_SESSION['nombre_usuario']
            ?? $_SESSION['user']
            ?? $_SESSION['nombre']
            ?? 'Administrador'
        );
        $this->compactLayout = true;

        $this->SetCreator('Idéalo');
        $this->SetAuthor('Idéalo');
        $this->SetTitle($title);
        $this->setPrintHeader(false);
        $this->setPrintFooter(true);
        $this->SetMargins(15, 12, 15);
        $this->SetAutoPageBreak(true, 18);
    }

    public function addCompactReportHeader(): void
    {
        $logoPath = __DIR__ . '/../../assets/Img/Iconos/Idealo_logo1.svg';
        if (file_exists($logoPath)) {
            $this->ImageSVG($logoPath, 15, 10, 14, 14);
        }

        $this->SetXY(32, 10);
        $this->SetFont('helvetica', 'B', 15);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(100, 7, 'Idéalo  -  ' . $this->reportTitle, 0, 0, 'L');

        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(100, 116, 139);
        $this->SetXY(135, 11);
        $this->Cell(60, 7, 'Usuario: ' . $this->generatedBy, 0, 1, 'R');

        $this->SetXY(32, 17);
        $this->SetFont('helvetica', '', 9.5);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(0, 5, $this->reportSubtitle, 0, 1, 'L');
        $this->Ln(4);
    }

    public function addMetricCards(array $cards): void
    {
        if ($cards === []) {
            return;
        }

        $cards = array_values($cards);
        $palette = [
            [[248, 250, 252], [226, 232, 240], [71, 85, 105], [15, 23, 42]],
            [[241, 245, 249], [203, 213, 225], [71, 85, 105], [51, 65, 85]],
            [[224, 242, 254], [186, 230, 253], [3, 105, 161], [12, 74, 110]],
            [[240, 253, 244], [187, 247, 208], [22, 101, 52], [20, 83, 45]],
        ];
        $gap = 2;
        $availableWidth = $this->getPageWidth() - 30 - $gap * (count($cards) - 1);
        $weights = array_map(
            static function ($card): float {
                return (float) ($card['width'] ?? 1);
            },
            $cards
        );
        $totalWeight = array_sum($weights);
        $y = $this->GetY();
        $fontSize = count($cards) > 4 ? 7 : 8;
        $x = 15;

        $this->SetLineWidth(0.3);
        foreach (array_values($cards) as $index => $card) {
            $cardWidth = $availableWidth * $weights[$index] / $totalWeight;
            [$fillColor, $drawColor, $labelColor, $valueColor] = $palette[$index % count($palette)];

            $this->SetFillColor(...$fillColor);
            $this->SetDrawColor(...$drawColor);
            $this->Rect($x, $y, $cardWidth, 14, 'DF');

            $labelWidth = $cardWidth * 0.58;
            $valueWidth = $cardWidth - $labelWidth - 2;
            $this->SetXY($x + 1, $y + 3);
            $this->SetFont('helvetica', 'B', $fontSize);
            $this->SetTextColor(...$labelColor);
            $this->Cell($labelWidth, 5, (string) ($card['label'] ?? ''), 0, 0, 'L');

            $this->SetFont('helvetica', 'B', count($cards) > 4 ? 9 : 10);
            $this->SetTextColor(...$valueColor);
            $this->Cell($valueWidth, 5, (string) ($card['value'] ?? ''), 0, 0, 'R');
            $x += $cardWidth + $gap;
        }

        $this->SetY($y + 26);
    }

    public function addTableHeader(array $columns, float $height = 8.5): void
    {
        $this->SetFont('helvetica', 'B', 10);
        $this->SetFillColor(30, 41, 59);
        $this->SetTextColor(255, 255, 255);

        foreach ($columns as $index => $column) {
            $this->Cell(
                (float) $column['width'],
                $height,
                (string) $column['label'],
                1,
                $index === array_key_last($columns) ? 1 : 0,
                (string) ($column['align'] ?? 'L'),
                true
            );
        }
    }

    public function addTableRow(array $cells, bool $alternate = false, float $height = 8): void
    {
        $shade = $alternate ? 248 : 255;
        $this->SetFillColor($shade, $alternate ? 250 : 255, $alternate ? 252 : 255);

        foreach ($cells as $index => $cell) {
            $textColor = $cell['textColor'] ?? [30, 41, 59];
            $this->SetFont(
                'helvetica',
                (string) ($cell['fontStyle'] ?? ''),
                (float) ($cell['fontSize'] ?? 9)
            );
            $this->SetTextColor(...$textColor);
            $this->Cell(
                (float) $cell['width'],
                $height,
                (string) $cell['text'],
                'B',
                $index === array_key_last($cells) ? 1 : 0,
                (string) ($cell['align'] ?? 'L'),
                true
            );
        }

        $this->SetFont('helvetica', '', 9);
    }

    public function Header(): void
    {
        $logoPath = __DIR__ . '/../../assets/Img/Iconos/Idealo_logo1.svg';
        $this->ImageSVG($logoPath, 12, 8, 18, 18);

        $margin = 12;
        $pageWidth = $this->getPageWidth();
        $metadataWidth = 52;
        $metadataX = $pageWidth - $margin - $metadataWidth;
        $titleX = 34;
        $titleWidth = $metadataX - $titleX - 4;

        $this->SetTextColor(30, 55, 65);
        $this->SetFont('helvetica', 'B', $this->getPageWidth() > 220 ? 14 : 12);
        $this->SetXY($titleX, 9);
        $this->Cell($titleWidth, 7, $this->reportTitle, 0, 1, 'L');

        $this->SetFont('helvetica', '', 7.5);
        $this->SetTextColor(88, 105, 112);
        $this->SetXY($titleX, 17);
        $this->MultiCell($titleWidth, 4, $this->reportSubtitle, 0, 'L', false, 1);

        $this->SetXY($metadataX, 9);
        $this->SetFont('helvetica', '', 7.5);
        $this->SetTextColor(75, 88, 94);
        $this->MultiCell(
            $metadataWidth,
            4.5,
            'Generado: ' . $this->generatedAt . "\nUsuario: " . $this->generatedBy,
            0,
            'R',
            false,
            1
        );

        $this->SetDrawColor(210, 221, 225);
        $this->SetLineWidth(0.25);
        $this->Line($margin, 31, $pageWidth - $margin, 31);
    }

    public function Footer(): void
    {
        if ($this->compactLayout) {
            $this->SetY(-12);
            $this->SetFont('helvetica', 'I', 8.5);
            $this->SetTextColor(100, 116, 139);
            $this->SetDrawColor(203, 213, 225);
            $this->SetLineWidth(0.2);
            $contentWidth = $this->getPageWidth() - 30;
            $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY());

            $this->Ln(2);
            $this->Cell($contentWidth * 95 / 180, 5, 'Idéalo - Sistema de Gestión | Impreso el ' . $this->generatedAt, 0, 0, 'L');
            $this->Cell($contentWidth * 85 / 180, 5, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
            return;
        }

        $margin = 12;
        $pageWidth = $this->getPageWidth();
        $this->SetY(-11);
        $this->SetDrawColor(220, 227, 230);
        $this->SetLineWidth(0.2);
        $this->Line($margin, $this->GetY() - 2, $pageWidth - $margin, $this->GetY() - 2);

        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(120, 132, 138);
        $this->Cell(($pageWidth - 2 * $margin) / 2, 6, 'Idéalo', 0, 0, 'L');
        $this->Cell(($pageWidth - 2 * $margin) / 2, 6, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
    }

    public function addSummaryCards(array $cards): void
    {
        if ($cards === []) {
            return;
        }

        $cellWidth = number_format(100 / count($cards), 2, '.', '');
        $html = '<table cellpadding="6" cellspacing="2" width="100%"><tr>';
        foreach ($cards as $card) {
            $label = htmlspecialchars((string) ($card['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            $value = htmlspecialchars((string) ($card['value'] ?? ''), ENT_QUOTES, 'UTF-8');
            $html .= '<td width="' . $cellWidth . '%" border="1" bordercolor="#DCE5E8" bgcolor="#F2F6F7" align="center">'
                . '<font color="#647780" size="8">' . $label . '</font><br />'
                . '<font color="#183B43" size="11"><b>' . $value . '</b></font></td>';
        }
        $html .= '</tr></table>';

        $this->writeHTML($html, true, false, true, false, '');
        $this->Ln(2);
    }

    public function addTotalBanner(string $label, string $value): void
    {
        $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $html = '<table cellpadding="6" cellspacing="0" width="100%" border="1" bordercolor="#B7D6C1">'
            . '<tr bgcolor="#E9F4EC">'
            . '<td width="70%" align="right"><font color="#244B33" size="10"><b>' . $label . '</b></font></td>'
            . '<td width="30%" align="right"><font color="#1F6B3B" size="11"><b>' . $value . '</b></font></td>'
            . '</tr></table>';

        $this->writeHTML($html, true, false, true, false, '');
    }
}
