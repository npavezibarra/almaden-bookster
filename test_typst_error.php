<?php
define( 'ALMADEN_TYPST_TESTING', true );
require_once __DIR__ . '/includes/pdf-typst/typst-fonts.php';
require_once __DIR__ . '/includes/pdf-typst/typst-markup.php';

$raw = <<<'RAW'
A estas alturas ya entiendes cómo funciona el proceso de compra y quiénes participan en él. Lo que muchos extranjeros no pueden visualizar antes de su primera compra es cuánto cuesta realmente cerrar una operación en Estados Unidos y qué se está pagando exactamente con cada uno de esos cargos.

En palabras gruesas, los costos de cierre en Estados Unidos suelen estar entre un 4% y un 6% del valor de la propiedad — en el ejemplo de este capítulo, una propiedad de USD 115.000, eso equivale a entre USD 4.600 y USD 6.900. Lo que normalmente puede aumentar ese costo es si te decides a comprar puntos para bajar la tasa de interés y, de esta forma, alivianar tus dividendos mensuales.

El documento que resume todos estos movimientos tiene muchos nombres, pero comúnmente se le llama *Settlement Statement* — o *Closing Disclosure* (CD). Lo emite el *title company* y normalmente lo recibes en los días previos al cierre. Es el resumen financiero completo de la operación: qué paga el comprador, qué recibe el vendedor y a quién le llega cada dólar.

A continuación, encontrarás el CD real de una compra. Es solo un modelo — cada *title company* usa un formato ligeramente diferente, aunque las secciones generalmente se repiten. Te recomiendo tenerlo a la vista mientras lees lo que sigue de este capítulo: cada vez que mencionemos una línea específica, podrás ubicarla directamente en la columna izquierda de la tabla.

Antes de entrar al detalle línea por línea, vale la pena entender el contexto de esta operación porque es distinto al de una compra estándar en el mercado abierto.

Esta propiedad fue adquirida a través de un *wholesaler* — uno de los miembros del equipo que vimos en el Capítulo 6 — quien encontró la propiedad con descuento e hizo un cierre doble.       cerró el *wholesaler* con el vendedor y luego el *wholesaler* me la vendió a mí. Todo el mismo día y en forma remota.

Mi financiamiento lo proveyó un *hard money lender* (HML), que me prestó el 90% del precio de compra. Como vimos en el Capítulo 8, los HML prestan a corto plazo (en este caso a 6 meses), a tasas más altas que un crédito DSCR — lo que los hace ideales para este tipo de operaciones donde la velocidad importa y la propiedad puede no estar en condiciones de ser financiada por un *lender* convencional.

El LTV del 90% explica por qué el *loan amount* (línea 3) es tan cercano al precio de compra. La rehabilitación posterior la realicé con fondos propios — no con dinero del HML — lo que es una decisión común cuando el costo del *rehab* es manejable y el inversionista prefiere no aumentar la deuda a corto plazo.

[box]
[html]
<div style="background:#f3f3f3;border:1px solid #d9d9d9;padding:14px 16px;border-radius:4px;font-size:6pt;line-height:1.25;text-align:left;">

<p style="margin-top:0;margin-bottom:6px;"><strong><em>CLOSING DISCLOSURE — EJEMPLO REAL</em></strong></p>

<p style="margin-bottom:4px;"><strong>Propiedad:</strong> 1247 Birchwood Ln, Birmingham, AL 35203</p>
<p style="margin-bottom:4px;"><strong>Comprador:</strong> Maple Investments LLC</p>
<p style="margin-bottom:4px;"><strong>Vendedor:</strong> R. Crawford</p>
<p style="margin-bottom:4px;"><strong>Fecha de cierre:</strong> 02/17/2026</p>
<p style="margin-bottom:4px;"><strong><em>Lender:</em></strong> Capital Lending LLC</p>
<p style="margin-bottom:8px;"><strong>Title company:</strong> Sunshine Title Services</p>

<p style="margin-bottom:8px;font-size:5.5pt;color:#555;">(Los nombres de las partes y la dirección han sido modificados. El resto replica la operación tal como ocurrió.)</p>

<table style="width:100%;border-collapse:collapse;text-align:left;">
<thead>
<tr style="border-bottom:1px solid #d9d9d9;">
<th style="padding:4px; width:50%;"><strong>Concepto</strong></th>
<th style="padding:4px; width:25%; text-align:right;"><strong>Comprador<br>(débito / crédito)</strong></th>
<th style="padding:4px; width:25%; text-align:right;"><strong>Vendedor<br>(débito / crédito)</strong></th>
</tr>
</thead>
<tbody>

<!-- SECCIÓN FINANCIAL -->
<tr>
<td colspan="3" style="padding:6px 4px 2px 4px; background-color:#eaeaea;"><strong>SECCIÓN FINANCIAL (líneas 1–5)</strong></td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">1. Precio de compra</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">+115.000,00</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">2. (EMD) — depositado en <em>escrow</em> al aceptarse la oferta</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">+2.500,00</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">3. Préstamo LLC (HML, 90% LTV)</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">+103.500,00</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">4. (costos del primer cierre traspasados por el <em>wholesaler</em>)</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">+1.177,25</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #d9d9d9;">5. Impuestos prorrateados: 10/01/2025 al 02/17/2026</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #d9d9d9;">+332,21</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #d9d9d9;">—</td>
</tr>

<!-- LOAN CHARGES -->
<tr>
<td colspan="3" style="padding:6px 4px 2px 4px; background-color:#eaeaea;"><strong>LOAN CHARGES (líneas 6–15)</strong></td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">6. Origination Fee — LLC</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">7. <em>Lender Attorney Review</em></td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">8. Points (~1,9% del monto prestado)</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">9–12. <em>Other Charges Lender</em></td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">13. <em>Lender's Title Policy</em></td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #d9d9d9;">15. Intereses prepagados: 02/17 al 03/01/2026</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #d9d9d9;">—</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #d9d9d9;">—</td>
</tr>

<!-- OTROS / TAXES -->
<tr>
<td colspan="3" style="padding:6px 4px 2px 4px; background-color:#eaeaea;"><strong>OTHER TAXES & GOVERNMENT FEES (líneas 16–19)</strong></td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">16. Recording Fees (transferencia USD 19 + hipoteca USD 88)</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #d9d9d9;">17–19. Transfer Taxes — Estado de Alabama</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #d9d9d9;">—</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #d9d9d9;">—</td>
</tr>

<!-- TITLE CHARGES -->
<tr>
<td colspan="3" style="padding:6px 4px 2px 4px; background-color:#eaeaea;"><strong>TITLE CHARGES (líneas 20–23)</strong></td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">20. <em>Title CPL (Closing Protection Letter) — lender</em></td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">22. Settlement / Closing Fee — Sunshine Title Services</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #d9d9d9;">23. Owner's Title Policy — seguro de título del comprador</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #d9d9d9;">—</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #d9d9d9;">—</td>
</tr>

<!-- MISCELLANEOUS -->
<tr>
<td colspan="3" style="padding:6px 4px 2px 4px; background-color:#eaeaea;"><strong>MISCELLANEOUS (líneas 24–26)</strong></td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">24. <em>Notary Fee — NotaryConnect Services</em></td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">POC</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #d9d9d9;">25. <em>Homeowner's Insurance Premium anual — Meridian Home Insurance</em></td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #d9d9d9;">POC</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #d9d9d9;">—</td>
</tr>

<!-- TOTALES -->
<tr>
<td colspan="3" style="padding:6px 4px 2px 4px; background-color:#eaeaea;"><strong>TOTALES</strong></td>
</tr>
<tr>
<td style="padding:3px 4px; border-bottom:1px solid #e5e5e5;">Total Débitos / Créditos del Comprador</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">+106.332,21</td>
<td style="padding:3px 4px; text-align:right; border-bottom:1px solid #e5e5e5;">—</td>
</tr>
<tr style="background-color:#e2f0d9;">
<td style="padding:6px 4px; border-bottom:1px solid #d9d9d9;"><strong>27. Due from Buyer (wire al <em>title company</em>)</strong></td>
<td style="padding:6px 4px; text-align:right; border-bottom:1px solid #d9d9d9;"><strong>USD 17.684,62</strong></td>
<td style="padding:6px 4px; text-align:right; border-bottom:1px solid #d9d9d9;">—</td>
</tr>
<tr style="background-color:#e2f0d9;">
<td style="padding:6px 4px;"><strong>28. Due to Seller (transferencia del <em>title company</em> al vendedor)</strong></td>
<td style="padding:6px 4px; text-align:right;">—</td>
<td style="padding:6px 4px; text-align:right;"><strong>USD 115.845,04</strong></td>
</tr>

</tbody>
</table>

</div>
[/html]
[/box]
RAW;

$rendered = almaden_bookster_typst_render_blocks( $raw );
file_put_contents('test_output.typ', $rendered);
system("/Users/nicolaspavez/Local\ Sites/almaden/app/public/wp-content/plugins/almaden-bookster/runtime/typst/typst compile test_output.typ test_output.pdf", $retval);
if ($retval !== 0) {
    echo "Error compiling typst\n";
} else {
    echo "Success!\n";
}
