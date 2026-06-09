<?php
class Api_FrontController extends TinyPHP_Controller {

    /**
     * POST /api/contact-inquiry
     * Public — no auth required.
     * Sends the contact form submission to the support inbox.
     */
    public function contactInquiryAction(TinyPHP_Request $request) {

        $name    = trim($request->getInput('name', ''));
        $email   = trim($request->getInput('email', ''));
        $company = trim($request->getInput('company', ''));
        $message = trim($request->getInput('message', ''));

        if (empty($name) || empty($email) || empty($message)) {
            return response([], 'Name, email, and message are required.', 422)->sendJson();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response([], 'Please enter a valid email address.', 422)->sendJson();
        }

        $appName   = config('app.name', 'Zentraq');
        $fromEmail = config('app.support_email', 'noreply@zentraqone.com');
        $toEmail   = 'ravipatel96013@gmail.com';

        $companyLine = $company ? "<tr><td style='padding:4px 0;color:#6b7280;width:100px;'>Company</td><td style='padding:4px 0;'>" . htmlspecialchars($company) . "</td></tr>" : '';

        $body = "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>
                <h2 style='color:#4f46e5;margin-bottom:4px;'>New Contact Inquiry</h2>
                <p style='color:#6b7280;margin-top:0;font-size:13px;'>Submitted via the {$appName} website</p>
                <table style='width:100%;border-collapse:collapse;margin-bottom:20px;font-size:14px;'>
                    <tr><td style='padding:4px 0;color:#6b7280;width:100px;'>Name</td><td style='padding:4px 0;'>" . htmlspecialchars($name) . "</td></tr>
                    <tr><td style='padding:4px 0;color:#6b7280;'>Email</td><td style='padding:4px 0;'><a href='mailto:" . htmlspecialchars($email) . "' style='color:#4f46e5;'>" . htmlspecialchars($email) . "</a></td></tr>
                    {$companyLine}
                </table>
                <div style='background:#f9fafb;border-left:4px solid #4f46e5;padding:12px 16px;font-size:14px;line-height:1.6;'>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
            </div>
        ";

        $mailer = new Helpers_Mailer();
        $mailer->setReplyTo($email, $name);
        $sent = $mailer->sendMail("{$appName} <{$fromEmail}>", $toEmail, "New inquiry from {$name}", $body);

        if (!$sent) {
            return response([], 'Failed to send your message. Please try again later.', 500)->sendJson();
        }

        return response([], 'Your message has been sent. We\'ll be in touch soon!', 200)->sendJson();
    }

}
