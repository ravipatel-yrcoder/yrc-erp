<?php
class Helpers_Pdf {

    public static function render(string $template, array $data, array $options = []): string
    {
        $templatePath = APP_PATH . '/resources/views/' . str_replace('.', '/', $template) . '.php';
        $html  = static::renderTemplate($templatePath, $data);
        $mpdf  = static::createMpdf($options);
        //echo $html;die;    

        $mpdf->WriteHTML($html);
        //echo $mpdf->Output('', 'I');die;

        return $mpdf->Output('', 'S');        
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

    public static function amountToWordsINR(float $amount): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen',
                 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $rupees = (int) floor(abs($amount));
        $paise  = (int) round((abs($amount) - $rupees) * 100);

        $belowHundred = function(int $n) use ($ones, $tens): string {
            if ($n < 20) return $ones[$n];
            return $tens[(int)($n / 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
        };

        $belowThousand = function(int $n) use ($ones, $belowHundred): string {
            if ($n < 100) return $belowHundred($n);
            return $ones[(int)($n / 100)] . ' Hundred' . ($n % 100 ? ' ' . $belowHundred($n % 100) : '');
        };

        $inWords = function(int $n) use ($belowThousand): string {
            if ($n === 0) return 'Zero';
            $parts = [];
            if ($n >= 10000000) { $parts[] = $belowThousand((int)($n / 10000000)) . ' Crore'; $n %= 10000000; }
            if ($n >= 100000)   { $parts[] = $belowThousand((int)($n / 100000))   . ' Lakh';  $n %= 100000; }
            if ($n >= 1000)     { $parts[] = $belowThousand((int)($n / 1000))     . ' Thousand'; $n %= 1000; }
            if ($n > 0)         { $parts[] = $belowThousand($n); }
            return implode(' ', $parts);
        };

        $result = 'Rupees ' . $inWords($rupees);
        if ($paise > 0) {
            $result .= ' and Paise ' . $inWords($paise);
        }
        return $result . ' Only';
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

        if (empty($options['no_footer'])) {
            $footerHtml = '<table width="100%"><tr>
                <td style="border-top:1pt solid #d1d5db;padding-top:5px;font-family:notosans;font-size:7.5pt;color:#9ca3af;text-align:left;">This is computer generated document.</td>
                <td style="border-top:1pt solid #d1d5db;padding-top:5px;font-family:notosans;font-size:7.5pt;color:#9ca3af;text-align:right;">Page {PAGENO} of {nbpg}</td>
            </tr></table>';
            $mpdf->SetHTMLFooter($footerHtml);
        }

        $mpdf->img_dpi = 300;

        if (!empty($options['watermark'])) {
            $mpdf->SetWatermarkText($options['watermark']);
            $mpdf->showWatermarkText  = true;
            $mpdf->watermarkTextAlpha = 0.08;
        }

        return $mpdf;
    }
}
