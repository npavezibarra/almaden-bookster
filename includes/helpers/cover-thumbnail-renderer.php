<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function almaden_get_cover_thumbnail_html( $book_id ) {
    $snapshot_disabled = ! empty( $GLOBALS['almaden_bookster_disable_cover_snapshot_resolve'] );
    $snapshot = $snapshot_disabled ? array() : almaden_bookster_get_cover_thumbnail_snapshot_metadata( $book_id );
    if ( ! empty( $snapshot ) && function_exists( 'almaden_bookster_get_cover_thumbnail_snapshot_version' ) ) {
        $current_version = almaden_bookster_get_cover_thumbnail_snapshot_version( $book_id );
        if ( empty( $snapshot['version'] ) || $snapshot['version'] !== $current_version ) {
            $snapshot = array();
        }
    }
    if ( ! empty( $snapshot ) ) {
        $snapshot_html = almaden_bookster_render_cover_thumbnail_snapshot_html( $book_id, $snapshot );
        if ( '' !== $snapshot_html ) {
            return $snapshot_html;
        }
    }

    $db_settings = almaden_bookster_get_cover_settings_row( $book_id );

    $page_width = isset($db_settings['page_width']) ? floatval($db_settings['page_width']) : 21.0;
    $page_height = isset($db_settings['page_height']) ? floatval($db_settings['page_height']) : 29.7;
    
    $cover_settings = get_post_meta( $book_id, '_almaden_cover_settings', true );
    if ( empty($cover_settings) || !is_array($cover_settings) ) {
        return '';
    }

    // Card thumbnails must use the same screen-safe assets as the cover editor.
    // This keeps CMYK originals for print while avoiding broken or color-shifted
    // images in the browser.
    if ( function_exists( 'almaden_bookster_prepare_cover_settings_for_editor' ) ) {
        $cover_settings = almaden_bookster_prepare_cover_settings_for_editor( $cover_settings );
    }
    $spread_preview_url = ! empty( $cover_settings['spread_image_preview_url'] ) ? $cover_settings['spread_image_preview_url'] : '';
    $front_preview_url = ! empty( $cover_settings['front_image_preview_url'] ) ? $cover_settings['front_image_preview_url'] : '';
    $back_preview_url = ! empty( $cover_settings['back_image_preview_url'] ) ? $cover_settings['back_image_preview_url'] : '';
    $spine_preview_url = ! empty( $cover_settings['spine_image_preview_url'] ) ? $cover_settings['spine_image_preview_url'] : '';

    // Check if there are any layers or front image
    if ( empty($cover_settings['text_layers']) && empty($cover_settings['front_image']) && empty($cover_settings['spread_image']) ) {
        return '';
    }

    $total_pages = get_post_meta( $book_id, '_almaden_total_pages', true );
    $pages = $total_pages ? intval( $total_pages ) : 20;
    if ($pages < 20) $pages = 20;

    $spineWidthMm = almaden_bookster_get_cover_spine_width_mm( $cover_settings, $pages );
    
    $frontFlapMm = isset($cover_settings['front_flap_width']) ? almaden_bookster_round_up_mm( $cover_settings['front_flap_width'] ) : 0;
    $backFlapMm = isset($cover_settings['back_flap_width']) ? almaden_bookster_round_up_mm( $cover_settings['back_flap_width'] ) : 0;
    $foldXMm = function_exists( 'almaden_bookster_get_cover_fold_x_mm' ) ? almaden_bookster_get_cover_fold_x_mm( $cover_settings ) : 0;
    
    $pxPerCm = 37.7952755906;
    $bleedPx = (5 / 10) * $pxPerCm; 

    $spineWidthPx = ($spineWidthMm / 10) * $pxPerCm;
    
    $frontFlapPx = ($frontFlapMm / 10) * $pxPerCm;
    $backFlapPx = ($backFlapMm / 10) * $pxPerCm;
    
    $pageWidthPx = $page_width * $pxPerCm;
    $pageHeightPx = $page_height * $pxPerCm;
    $actualHeightPx = $pageHeightPx + (2 * $bleedPx);

    $frontCoverPx = $pageWidthPx;
    $backCoverPx = $pageWidthPx;

    if ( $foldXMm > 0 && ( $frontFlapMm > 0 || $backFlapMm > 0 ) ) {
        $foldXPx = ( $foldXMm / 10 ) * $pxPerCm;
        $frontCoverPx += $foldXPx;
        $backCoverPx += $foldXPx;
    }

    if ($frontFlapMm > 0) $frontFlapPx += $bleedPx; else $frontCoverPx += $bleedPx;
    if ($backFlapMm > 0) $backFlapPx += $bleedPx; else $backCoverPx += $bleedPx;

    $totalSpreadWidthPx = $frontCoverPx + $backCoverPx + $spineWidthPx + $frontFlapPx + $backFlapPx;
    $frontCoverStartPx = $backFlapPx + $backCoverPx + $spineWidthPx;

    $aspectRatio = $pageWidthPx / $pageHeightPx;

    ob_start();
    ?>
    <div class="cover-thumbnail-wrapper w-full bg-white overflow-hidden relative border-b border-gray-200" 
         data-front-cover-px="<?php echo esc_attr($pageWidthPx); ?>"
         data-start-px="<?php echo esc_attr($frontCoverStartPx); ?>"
         data-start-y-px="<?php echo esc_attr($bleedPx); ?>"
         style="aspect-ratio: <?php echo round($aspectRatio, 4); ?>;">
        
        <div class="cover-spread-container absolute top-0 left-0" 
             style="width: <?php echo $totalSpreadWidthPx; ?>px; height: <?php echo $actualHeightPx; ?>px; transform-origin: top left; pointer-events: none;">
            
            <?php if ( !empty($spread_preview_url) ) : ?>
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo esc_url($spread_preview_url); ?>');"></div>
            <?php else : ?>
                <?php if ( !empty($front_preview_url) ) : ?>
                    <div class="absolute top-0 bottom-0 bg-cover bg-center" style="left: <?php echo $frontCoverStartPx; ?>px; width: <?php echo $frontCoverPx; ?>px; background-image: url('<?php echo esc_url($front_preview_url); ?>');"></div>
                <?php endif; ?>
                <?php if ( !empty($back_preview_url) ) : ?>
                    <div class="absolute top-0 bottom-0 bg-cover bg-center" style="left: <?php echo $backFlapPx; ?>px; width: <?php echo $backCoverPx; ?>px; background-image: url('<?php echo esc_url($back_preview_url); ?>');"></div>
                <?php endif; ?>
                <?php if ( !empty($spine_preview_url) ) : ?>
                    <div class="absolute top-0 bottom-0 bg-cover bg-center" style="left: <?php echo ($backFlapPx + $backCoverPx); ?>px; width: <?php echo $spineWidthPx; ?>px; background-image: url('<?php echo esc_url($spine_preview_url); ?>');"></div>
                <?php elseif ( !empty($cover_settings['spine_color']) ) : ?>
                    <div class="absolute top-0 bottom-0 bg-cover bg-center" style="left: <?php echo ($backFlapPx + $backCoverPx); ?>px; width: <?php echo $spineWidthPx; ?>px; background-color: <?php echo esc_attr($cover_settings['spine_color']); ?>;"></div>
                <?php endif; ?>
            <?php endif; ?>

            <?php
            if ( !empty($cover_settings['text_layers']) && is_array($cover_settings['text_layers']) ) {
                $layers = $cover_settings['text_layers'];
                usort($layers, function($a, $b) {
                    $za = isset($a['zIndex']) ? intval($a['zIndex']) : 0;
                    $zb = isset($b['zIndex']) ? intval($b['zIndex']) : 0;
                    return $za - $zb;
                });

                foreach ( $layers as $layer ) {
                    if ( empty($layer['id']) ) continue;
                    
                    $x = isset($layer['x']) ? floatval($layer['x']) : 0;
                    $y = isset($layer['y']) ? floatval($layer['y']) : 0;
                    $rot = isset($layer['rotation']) ? floatval($layer['rotation']) : 0;
                    $zIndex = isset($layer['zIndex']) ? intval($layer['zIndex']) : 30;
                    $type = isset($layer['type']) ? $layer['type'] : 'text';

                    $style = "position: absolute; left: {$x}%; top: {$y}%; transform: rotate({$rot}deg); z-index: {$zIndex}; ";

                    if ($type === 'image' && !empty($layer['url'])) {
                        $lw = isset($layer['width']) ? floatval($layer['width']) : 200;
                        $lh = isset($layer['height']) ? floatval($layer['height']) : 200;
                        $style .= "width: {$lw}px; height: {$lh}px; background-image: url('".esc_url($layer['url'])."'); background-size: contain; background-repeat: no-repeat; background-position: center;";
                        echo "<div style=\"{$style}\"></div>";
                    } elseif ($type === 'shape') {
                        $lw = isset($layer['width']) ? floatval($layer['width']) : 150;
                        $lh = isset($layer['height']) ? floatval($layer['height']) : 150;
                        $opacity = isset($layer['opacity']) ? floatval($layer['opacity'])/100 : 1;
                        $shapeType = isset($layer['shapeType']) ? $layer['shapeType'] : 'rectangle';
                        $br = ($shapeType === 'circle') ? '50%' : '0';
                        
                        $hex1 = isset($layer['color1']) ? $layer['color1'] : '#000000';
                        $op1 = isset($layer['color1Opacity']) ? floatval($layer['color1Opacity']) / 100 : 1;
                        if (preg_match('/^#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/i', $hex1, $m)) {
                            $c1 = "rgba(" . hexdec($m[1]) . ", " . hexdec($m[2]) . ", " . hexdec($m[3]) . ", {$op1})";
                        } else {
                            $c1 = $hex1;
                        }

                        $isGradient = ! empty( $layer['isGradient'] ) && ( $layer['isGradient'] === true || $layer['isGradient'] === 'true' || $layer['isGradient'] === '1' );
                        if ($isGradient) {
                            $hex2 = isset($layer['color2']) ? $layer['color2'] : '#ffffff';
                            $op2 = isset($layer['color2Opacity']) ? floatval($layer['color2Opacity']) / 100 : 1;
                            if (preg_match('/^#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/i', $hex2, $m)) {
                                $c2 = "rgba(" . hexdec($m[1]) . ", " . hexdec($m[2]) . ", " . hexdec($m[3]) . ", {$op2})";
                            } else {
                                $c2 = $hex2;
                            }
                            $angle = isset($layer['gradientAngle']) ? $layer['gradientAngle'] : '90';
                            $bg = "linear-gradient({$angle}deg, {$c1}, {$c2})";
                        } else {
                            $bg = $c1;
                        }
                        
                        $style .= "width: {$lw}px; height: {$lh}px; opacity: {$opacity}; border-radius: {$br}; background: {$bg};";
                        echo "<div style=\"{$style}\"></div>";
                    } else {
                        // text
                        $fontSize = isset($layer['fontSize']) ? floatval($layer['fontSize']) : 12;
                        $fontWeight = isset($layer['fontWeight']) ? sanitize_text_field($layer['fontWeight']) : '400';
                        $fontStyle = isset($layer['fontStyle']) ? sanitize_text_field($layer['fontStyle']) : 'normal';
                        $lineHeight = isset($layer['lineHeight']) && $layer['lineHeight'] !== '' ? floatval($layer['lineHeight']) : 1.2;
                        $letterSpacing = isset($layer['letterSpacing']) && $layer['letterSpacing'] !== '' ? floatval($layer['letterSpacing']) : 0;
                        $color = isset($layer['color']) ? esc_attr($layer['color']) : '#000000';
                        $fontFamily = isset($layer['fontFamily']) ? esc_attr($layer['fontFamily']) : 'Inter';
                        $textAlign = isset($layer['textAlign']) ? esc_attr($layer['textAlign']) : 'center';
                        $w = isset($layer['width']) && $layer['width'] ? floatval($layer['width']).'px' : 'auto';
                        $h = isset($layer['height']) && $layer['height'] ? floatval($layer['height']).'px' : 'auto';
                        $text = isset($layer['text']) ? esc_html($layer['text']) : '';
                        $hyphens = !empty($layer['hyphens']) ? 'auto' : 'none';

                        $style .= "box-sizing: border-box; width: {$w}; height: {$h}; font-size: {$fontSize}px; font-weight: {$fontWeight}; font-style: {$fontStyle}; color: {$color}; font-family: '{$fontFamily}', sans-serif, serif; text-align: {$textAlign}; white-space: pre-wrap; line-height: {$lineHeight}; letter-spacing: {$letterSpacing}px; font-synthesis: none; hyphens: {$hyphens}; -webkit-hyphens: {$hyphens};";
                        echo "<div data-cover-text-layer=\"1\" style=\"{$style}\">{$text}</div>";
                    }
                }
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
