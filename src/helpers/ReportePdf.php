<?php

namespace Idealo\Helpers;

class ReportePdf extends \TCPDF
{
    private string $reportTitle = '';
    private string $reportSubtitle = '';
    private string $generatedAt = '';
    private string $generatedBy = '';

    public function setReportDetails(string $title, string $subtitle = ''): void
    {
        $this->reportTitle = $title;
        $this->reportSubtitle = $subtitle;
        $this->generatedAt = date('d/m/Y H:i:s');
        $this->generatedBy = (string) ($_SESSION['nombre_usuario'] ?? 'Usuario no identificado');

        $this->SetMargins(12, 36, 12);
        $this->SetHeaderMargin(7);
        $this->SetFooterMargin(10);
        $this->SetAutoPageBreak(true, 15);
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);
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
