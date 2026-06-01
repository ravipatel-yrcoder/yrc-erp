<?php
class Helpers_Pdf {

    public static function render(string $template, array $data, array $options = []): string
    {
        $templatePath = APP_PATH . '/resources/views/' . str_replace('.', '/', $template) . '.php';
        $html  = static::renderTemplate($templatePath, $data);
        $mpdf  = static::createMpdf($options);

        $mpdf->WriteHTML($html);
        $mpdf->Output('', 'I');        
    }

    public static function stream(string $pdfBytes, string $filename, string $mode = 'inline'): void
    {
        $disposition = ($mode === 'download') ? 'attachment' : 'inline';
        header('Content-Type: application/pdf');
        header("Content-Disposition: {$disposition}; filename=\"{$filename}\"");
        header('Content-Length: ' . strlen($pdfBytes));
        header('Cache-Control: no-cache, no-store');
        echo $pdfBytes;
        exit;
    }

    public static function assetPath(string $relativeUrl): string
    {
        return ROOT_PATH . '/Public' . $relativeUrl;
    }

    private static function renderTemplate(string $templatePath, array $data): string
    {
        extract($data);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    private static function createMpdf(array $options = []): \Mpdf\Mpdf
    {
        $cfg = config('pdf');

        $fontConfig    = new \Mpdf\Config\FontVariables();
        $defaultFonts  = $fontConfig->getDefaults();
        $fontDataConfig = new \Mpdf\Config\ConfigVariables();
        $defaultConfig  = $fontDataConfig->getDefaults();

        $mpdf = new \Mpdf\Mpdf([
            'mode'           => 'utf-8',
            'format'         => $cfg['paper'],
            'margin_left'    => $cfg['margin_left'],
            'margin_right'   => $cfg['margin_right'],
            'margin_top'     => $cfg['margin_top'],
            'margin_bottom'  => $cfg['margin_bottom'],
            'margin_footer'  => $cfg['margin_footer'],
            'default_font'   => $cfg['default_font'],
            'tempDir'        => $cfg['temp_dir'],
            'fontDir'        => array_merge($defaultConfig['fontDir'], [$cfg['font_dir']]),
            'fontdata'       => array_merge($defaultFonts['fontdata'], $cfg['fonts']),
        ]);

        $footerHtml = '<table width="100%"><tr>
            <td style="border-top:1pt solid #d1d5db;padding-top:5px;font-family:notosans;font-size:7.5pt;color:#9ca3af;text-align:left;">Thank you for your business.</td>
            <td style="border-top:1pt solid #d1d5db;padding-top:5px;font-family:notosans;font-size:7.5pt;color:#9ca3af;text-align:right;">Page {PAGENO} of {nbpg}</td>
        </tr></table>';
        $mpdf->SetHTMLFooter($footerHtml);

        $mpdf->img_dpi = 300;

        if (!empty($options['watermark'])) {
            $mpdf->SetWatermarkText($options['watermark']);
            $mpdf->showWatermarkText  = true;
            $mpdf->watermarkTextAlpha = 0.08;
        }

        return $mpdf;
    }
}
