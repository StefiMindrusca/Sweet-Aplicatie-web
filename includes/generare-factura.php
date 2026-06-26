<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function afiseazaValoare($valoare)
{
    if (!isset($valoare) || trim($valoare) === "") {
        return "-";
    }

    return htmlspecialchars($valoare);
}

function genereazaFactura($order_id)
{
    global $conn;

    $order_id = (int)$order_id;

    $order_result = mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id LIMIT 1");

    if (!$order_result || mysqli_num_rows($order_result) == 0) {
        return false;
    }

    $order = mysqli_fetch_assoc($order_result);

    $items_result = mysqli_query(
        $conn,
        "SELECT * FROM order_items
         WHERE order_id = $order_id AND cutie_id IS NULL"
    );

    $rows = "";

    while ($item = mysqli_fetch_assoc($items_result)) {
        $cantitate_parinte = (int)$item["quantity"];
        $subtotal = (float)$item["price"] * $cantitate_parinte;

        $rows .= "
            <tr>
                <td>" . htmlspecialchars($item["product_name"]) . "</td>
                <td class='center'>" . $cantitate_parinte . "</td>
                <td class='right'>" . number_format($item["price"], 2) . " lei</td>
                <td class='right'>" . number_format($subtotal, 2) . " lei</td>
            </tr>
        ";

        if ((int)$item["e_cutie"] == 1) {
            $id_cutie = (int)$item["id"];

            $continut_result = mysqli_query(
                $conn,
                "SELECT * FROM order_items
                 WHERE cutie_id = $id_cutie"
            );

            while ($produs_cutie = mysqli_fetch_assoc($continut_result)) {
                $cantitate_produs_cutie = (int)$produs_cutie["quantity"] * $cantitate_parinte;
                $subtotal_cutie = (float)$produs_cutie["price"] * $cantitate_produs_cutie;

                $rows .= "
                    <tr class='box-item'>
                        <td class='box-product-name'>" . htmlspecialchars($produs_cutie["product_name"]) . "</td>
                        <td class='center'>" . $cantitate_produs_cutie . "</td>
                        <td class='right'>" . number_format($produs_cutie["price"], 2) . " lei</td>
                        <td class='right'>" . number_format($subtotal_cutie, 2) . " lei</td>
                    </tr>
                ";
            }
        }
    }

    $transport = (float)($order["transport"] ?? 0);
    $transport_text = $transport > 0 ? number_format($transport, 2) . " lei" : "GRATUIT";

    $metoda_livrare = $order["delivery_method"] ?? "domiciliu";

    $metoda_plata = ($order["metoda_plata"] ?? "card") == "cash"
        ? "Plată la livrare / ridicare"
        : "Card online";

    if ($metoda_livrare == "ridicare") {
        $info_livrare = "
            Metodă de livrare: Ridicare personală<br>
            Metodă de plată: " . htmlspecialchars($metoda_plata) . "
        ";
    } else {
        $info_livrare = "
            Metodă de livrare: Livrare la domiciliu<br>
            Adresă: " . afiseazaValoare($order["adresa"] ?? "") . "<br>
            Județ: " . afiseazaValoare($order["judet"] ?? "") . "<br>
            Localitate: " . afiseazaValoare($order["oras"] ?? "") . "<br>
            Cod poștal: " . afiseazaValoare($order["cod_postal"] ?? "") . "<br>
            Metodă de plată: " . htmlspecialchars($metoda_plata) . "
        ";
    }

    $html = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body {
                font-family: 'Times New Roman', DejaVu Serif, serif;
                color: #111;
                font-size: 14px;
                line-height: 1.4;
            }

            .header {
                text-align: center;
                padding-bottom: 14px;
                margin-bottom: 28px;
            }

            .brand {
                font-size: 38px;
                font-weight: bold;
                letter-spacing: 2px;
                margin-bottom: 8px;
            }

            .invoice-title {
                font-size: 22px;
                font-weight: bold;
                margin-bottom: 4px;
            }

            .invoice-date {
                color: #555;
                font-size: 15px;
            }

            .info-table {
                width: 100%;
                margin-bottom: 30px;
                border-collapse: collapse;
            }

            .info-table td {
                width: 50%;
                vertical-align: top;
                border-bottom: none;
                padding: 0 20px 0 0;
            }

            .info-table td:last-child {
                padding: 0 0 0 20px;
            }

            .info-title {
                font-size: 17px;
                font-weight: bold;
                border-bottom: 1px solid #f0d5df;
                padding-bottom: 6px;
                margin-bottom: 10px;
            }

            table.products {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }

            table.products th {
                background: #f6d6e2;
                padding: 10px;
                text-align: left;
                font-weight: bold;
            }

            table.products td {
                padding: 9px 10px;
                border-bottom: 1px solid #eeeeee;
            }

            .center {
                text-align: center;
            }

            .right {
                text-align: right;
            }

            .box-item td {
                color: #777;
                font-size: 12px;
                padding-top: 4px;
                padding-bottom: 4px;
                border-bottom: none !important;
            }

            .box-product-name {
                padding-left: 28px !important;
            }

            .transport-row td {
                padding-top: 12px;
                font-weight: bold;
            }

            .total {
                margin-top: 25px;
                text-align: right;
                font-size: 22px;
                font-weight: bold;
            }
        </style>
    </head>

    <body>

        <div class='header'>
            <div class='brand'>SWEET</div>
            <div class='invoice-title'>Factură comandă #" . (int)$order["id"] . "</div>
            <div class='invoice-date'>Data: " . date('d.m.Y', strtotime($order["created_at"])) . "</div>
        </div>

        <table class='info-table'>
            <tr>
                <td>
                    <div class='info-title'>Date comerciant</div>
                    <strong>Sweet</strong><br>
                    Str. Soarelui nr. 500<br>
                    Județ: Cluj<br>
                    Localitate: Cluj-Napoca<br>
                    Email: sweetlicenta@gmail.com
                </td>

                <td>
                    <div class='info-title'>Date client</div>
                    <strong>" . afiseazaValoare(($order["prenume"] ?? "") . " " . ($order["nume"] ?? "")) . "</strong><br>
                    Telefon: " . afiseazaValoare($order["telefon"] ?? "") . "<br>
                    Email: " . afiseazaValoare($order["email"] ?? "") . "<br><br>
                    " . $info_livrare . "
                </td>
            </tr>
        </table>

        <table class='products'>
            <thead>
                <tr>
                    <th>Produs</th>
                    <th class='center'>Cantitate</th>
                    <th class='right'>Preț unitar</th>
                    <th class='right'>Total</th>
                </tr>
            </thead>

            <tbody>
                $rows

                <tr class='transport-row'>
                    <td colspan='3'>Transport</td>
                    <td class='right'>$transport_text</td>
                </tr>
            </tbody>
        </table>

        <div class='total'>
            TOTAL: " . number_format($order["total"], 2) . " lei
        </div>

    </body>
    </html>
    ";

    $options = new Options();
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfPath = __DIR__ . '/../facturi/factura_comanda_' . $order_id . '.pdf';

    file_put_contents($pdfPath, $dompdf->output());

    return $pdfPath;
}