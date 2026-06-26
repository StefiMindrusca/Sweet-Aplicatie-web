<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;

function configureMail($order) {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress($order["email"], $order["prenume"] . " " . $order["nume"]);
    $mail->isHTML(true);
    return $mail;
}

function buildSummaryHtml($order, $items) {
    $html  = "<table style='width:100%;border-collapse:collapse;margin:20px 0;font-size:15px;'>";
    $html .= "<tr style='border-bottom:2px solid #eee;'>";
    $html .= "<th style='text-align:left;padding:8px 4px;'>Produs</th>";
    $html .= "<th style='text-align:right;padding:8px 4px;'>Pret</th>";
    $html .= "</tr>";

    $copii_pe_cutie = array();
    foreach ($items as $item) {
        $cid = $item["cutie_id"] ?? null;
        if ($cid !== null && $cid !== "") {
            if (!isset($copii_pe_cutie[$cid])) {
                $copii_pe_cutie[$cid] = array();
            }
            $copii_pe_cutie[$cid][] = $item;
        }
    }

    foreach ($items as $item) {
        $cid = $item["cutie_id"] ?? null;
        if ($cid !== null && $cid !== "") {
            continue;
        }

        $linie = number_format($item["price"] * $item["quantity"], 2);
        $este_cutie = (int)($item["e_cutie"] ?? 0) == 1;

        $border_stil = $este_cutie ? "" : "border-bottom:1px solid #f5f5f5;";
        $html .= "<tr style='$border_stil'>";
        $html .= "<td style='padding:8px 4px;'>" . htmlspecialchars($item["product_name"]) . " x" . (int)$item["quantity"] . "</td>";
        $html .= "<td style='text-align:right;padding:8px 4px;'>" . $linie . " lei</td>";
        $html .= "</tr>";

        if ($este_cutie) {
            $id_cutie = $item["id"];
            if (isset($copii_pe_cutie[$id_cutie])) {
                $nr_copii = count($copii_pe_cutie[$id_cutie]);
                foreach ($copii_pe_cutie[$id_cutie] as $idx => $produs_cutie) {
                    $ultimul = ($idx == $nr_copii - 1);
                    $border = $ultimul ? "border-bottom:1px solid #f5f5f5;" : "";
                    $subtotal_cutie = number_format($produs_cutie["price"] * $produs_cutie["quantity"], 2);
                    $html .= "<tr style='color:#888;font-size:12px;$border'>";
                    $html .= "<td style='padding:2px 4px 2px 20px;'>" . htmlspecialchars($produs_cutie["product_name"]) . " x" . (int)$produs_cutie["quantity"] . "</td>";
                    $html .= "<td style='text-align:right;padding:2px 4px;'>" . $subtotal_cutie . " lei</td>";
                    $html .= "</tr>";
                }
            }
        }
    }

    $transport = (float)($order["transport"] ?? 0);
    $transport_text = $transport > 0 ? number_format($transport, 2) . " lei" : "GRATUIT";
    $html .= "<tr style='border-bottom:1px solid #eee;color:#555;'>";
    $html .= "<td style='padding:8px 4px;'>Transport</td>";
    $html .= "<td style='text-align:right;padding:8px 4px;'>" . $transport_text . "</td>";
    $html .= "</tr>";

    $html .= "<tr style='font-weight:bold;'>";
    $html .= "<td style='padding:10px 4px;'>Total</td>";
    $html .= "<td style='text-align:right;padding:10px 4px;'>" . number_format($order["total"], 2) . " lei</td>";
    $html .= "</tr>";

    $html .= "</table>";
    return $html;
}

function sendOrderConfirmationEmail($order, $items) {
    $mail = configureMail($order);

    $livrare = ($order["delivery_method"] ?? "domiciliu") == "ridicare"
        ? "Ridicare din magazin - Str. Soarelui nr. 500, Cluj-Napoca"
        : "Livrare la domiciliu - " . htmlspecialchars($order["adresa"] . ", " . $order["oras"]);

    $summary = buildSummaryHtml($order, $items);

    $mail->Subject = "Comanda #" . $order["id"] . " plasata cu succes - Sweet";
    $mail->Body =
        "<h2 style='color:#222;'>Buna, " . htmlspecialchars($order["prenume"]) . "!</h2>" .
        "<p>Iti multumim pentru comanda. In curand vei primi un mail cu confirmarea comenzii.</p>" .
        "<p><strong>Numar comanda:</strong> #" . $order["id"] . "</p>" .
        "<p><strong>Livrare:</strong> " . $livrare . "</p>" .
        $summary .
        "<p>Poti urmari statusul comenzii in profilul tau de client.</p>" .
        "<br><p>Cu drag,<br><strong>Echipa Sweet</strong></p>";

    $mail->AltBody = "Comanda #" . $order["id"] . " confirmata. Total: " . number_format($order["total"], 2) . " lei.";
    $mail->send();
}

function sendOrderStatusEmail($order, $status, $pdfPath = null) {
    $mesaje = [
        "confirmata"      => ["Comanda ta Sweet a fost confirmata",      "Comanda ta a fost confirmata si va fi pregatita in curand."],
        "in pregatire"    => ["Comanda ta Sweet este in pregatire",      "Lucram la comanda ta si o pregatim cu grija."],
        "in livrare"      => ["Comanda ta Sweet este in livrare",        "Comanda ta este pe drum catre tine!"],
        "livrata"         => ["Comanda ta Sweet a fost livrata",         "Comanda ta a fost finalizata. Iti multumim!"],
        "gata de ridicat" => ["Comanda ta Sweet este gata de ridicat",   "Poti veni sa ridici comanda de la magazinul nostru."],
        "ridicata"        => ["Comanda ta Sweet a fost ridicata",        "Comanda ta a fost ridicata. Iti multumim!"],
        "anulata"         => ["Comanda ta Sweet a fost anulata",         "Comanda ta a fost anulata. Pentru detalii, ne poti contacta."],
    ];

    if (!isset($mesaje[$status])) {
        return;
    }

    list($subject, $message) = $mesaje[$status];

    $mail = configureMail($order);
    $mail->Subject = $subject;
    $mail->Body =
        "<h2 style='color:#222;'>Buna, " . htmlspecialchars($order["prenume"]) . "!</h2>" .
        "<p>" . $message . "</p>" .
        "<p><strong>Comanda:</strong> #" . $order["id"] . "</p>" .
        "<p><strong>Total:</strong> " . number_format($order["total"], 2) . " lei</p>" .
        "<p>Poti urmari detaliile comenzii in profilul tau de client.</p>" .
        "<br><p>Cu drag,<br><strong>Echipa Sweet</strong></p>";

    $mail->AltBody = $message;

    if ($pdfPath && file_exists($pdfPath)) {
        $mail->addAttachment($pdfPath);
    }

    $mail->send();
}
