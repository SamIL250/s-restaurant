<?php

require_once __DIR__ . '/../../config/app.php';

class OrderNotifier
{
    public static function notify(array $order, array $customer, array $items): array
    {
        $whatsappUrl = self::buildWhatsAppUrl($order, $customer, $items);
        $emailSent = self::sendCustomerEmail($order, $customer, $items);
        $restaurantEmailSent = self::sendRestaurantEmail($order, $customer, $items);

        return [
            'whatsapp_url' => $whatsappUrl,
            'email_sent' => $emailSent,
            'restaurant_notified' => $restaurantEmailSent,
        ];
    }

    private static function buildWhatsAppUrl(array $order, array $customer, array $items): string
    {
        $lines = [
            'New order from Smart Resto website',
            'Order #: ' . $order['order_number'],
            'Customer: ' . trim($customer['name']),
            'Phone: ' . $customer['phone'],
            'Email: ' . $customer['email'],
            'Type: ' . str_replace('_', ' ', $order['order_type']),
        ];

        if (!empty($order['delivery_address'])) {
            $lines[] = 'Address: ' . $order['delivery_address'];
        }

        $lines[] = '';
        $lines[] = 'Items:';
        foreach ($items as $item) {
            $lines[] = '- ' . $item['name'] . ' x' . $item['quantity'] . ' (Frw ' . number_format($item['total_price'], 2) . ')';
        }

        $lines[] = '';
        $lines[] = 'Total: Frw ' . number_format((float) $order['total_amount'], 2);

        if (!empty($order['special_instructions'])) {
            $lines[] = 'Notes: ' . $order['special_instructions'];
        }

        $message = implode("\n", $lines);

        return 'https://wa.me/' . RESTAURANT_WHATSAPP . '?text=' . rawurlencode($message);
    }

    private static function sendCustomerEmail(array $order, array $customer, array $items): bool
    {
        if (empty($customer['email'])) {
            return false;
        }

        $subject = RESTAURANT_NAME . ' - Order Confirmation #' . $order['order_number'];
        $body = self::buildEmailHtml($order, $customer, $items, 'We received your order and will confirm it shortly via WhatsApp or phone.');

        return self::sendEmail($customer['email'], $subject, $body);
    }

    private static function sendRestaurantEmail(array $order, array $customer, array $items): bool
    {
        $subject = 'New Website Order #' . $order['order_number'];
        $body = self::buildEmailHtml($order, $customer, $items, 'A new order was placed on the website.');

        return self::sendEmail(RESTAURANT_EMAIL, $subject, $body);
    }

    private static function buildEmailHtml(array $order, array $customer, array $items, string $intro): string
    {
        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr>'
                . '<td>' . htmlspecialchars($item['name']) . '</td>'
                . '<td>' . (int) $item['quantity'] . '</td>'
                . '<td>Frw ' . number_format((float) $item['unit_price'], 2) . '</td>'
                . '<td>Frw ' . number_format((float) $item['total_price'], 2) . '</td>'
                . '</tr>';
        }

        return '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;color:#333;">'
            . '<h2>' . htmlspecialchars(RESTAURANT_NAME) . '</h2>'
            . '<p>' . htmlspecialchars($intro) . '</p>'
            . '<p><strong>Order #:</strong> ' . htmlspecialchars($order['order_number']) . '<br>'
            . '<strong>Customer:</strong> ' . htmlspecialchars($customer['name']) . '<br>'
            . '<strong>Phone:</strong> ' . htmlspecialchars($customer['phone']) . '<br>'
            . '<strong>Email:</strong> ' . htmlspecialchars($customer['email']) . '<br>'
            . '<strong>Order type:</strong> ' . htmlspecialchars(str_replace('_', ' ', $order['order_type'])) . '</p>'
            . '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;">'
            . '<thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>'
            . '<p><strong>Total:</strong> Frw ' . number_format((float) $order['total_amount'], 2) . '</p>'
            . '<p>Payment will be arranged directly with the restaurant. No online payment is required.</p>'
            . '</body></html>';
    }

    private static function sendEmail(string $to, string $subject, string $htmlBody): bool
    {
        $autoload = __DIR__ . '/../../admin/vendor/autoload.php';
        if (!file_exists($autoload)) {
            return false;
        }

        require_once $autoload;

        $mailConfig = __DIR__ . '/../../admin/config/mail_config.php';
        if (!file_exists($mailConfig)) {
            return false;
        }

        require_once $mailConfig;

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = MAIL_AUTH;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_SECURE;
            $mail->Port = MAIL_PORT;
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->send();
            return true;
        } catch (Throwable $e) {
            error_log('Order email failed: ' . $e->getMessage());
            return false;
        }
    }
}

?>
