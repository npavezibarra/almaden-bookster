<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function almaden_get_thumbnail_fonts_url() {
    require_once dirname(__FILE__) . '/../admin/admin-fonts.php';
    if (!function_exists('almaden_bookster_get_installed_fonts_list')) return '';
    
    $installed_fonts = almaden_bookster_get_installed_fonts_list();
    $font_families_for_cdn = array();
    $font_families_for_cdn[] = 'Inter:wght@100;200;300;400;500;600;700;800;900';
    $font_families_for_cdn[] = 'Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900';
    $font_families_for_cdn[] = 'Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900';

    foreach ( $installed_fonts as $ifont ) {
        $family_slug = str_replace( ' ', '+', $ifont['family'] );
        $variants_str = isset($ifont['variants']) ? $ifont['variants'] : '';
        if ( empty($variants_str) ) {
            $font_families_for_cdn[] = $family_slug . ':ital,wght@0,400;0,700;1,400';
            continue;
        }

        $variants_arr = explode(',', $variants_str);
        $tuples = array();
        foreach ( $variants_arr as $v ) {
            $v = trim($v);
            if ( empty($v) ) continue;
            
            $ital = 0;
            $wght = 400;
            
            if ( strpos($v, 'italic') !== false ) {
                $ital = 1;
                $w_str = str_replace('italic', '', $v);
                if ( $w_str === '' || $w_str === 'regular' ) {
                    $wght = 400;
                } else {
                    $wght = intval($w_str);
                }
            } else {
                if ( $v === 'regular' ) {
                    $wght = 400;
                } else {
                    $wght = intval($v);
                }
            }
            
            if ($wght >= 100 && $wght <= 900) {
                $tuples[] = $ital . ',' . $wght;
            }
        }
        
        if ( empty($tuples) ) {
            $font_families_for_cdn[] = $family_slug . ':ital,wght@0,400;0,700;1,400';
        } else {
            sort($tuples);
            $font_families_for_cdn[] = $family_slug . ':ital,wght@' . implode(';', $tuples);
        }
    }
    return 'https://fonts.googleapis.com/css2?' . implode( '&', array_map( function( $f ) { return 'family=' . $f; }, $font_families_for_cdn ) ) . '&display=swap';
}

function almaden_get_cover_thumbnail_html( $book_id ) {
    global $wpdb;
    $settings_table = $wpdb->prefix . 'almaden_book_settings';
    $db_settings = array();
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$settings_table'" ) === $settings_table ) {
        $db_settings = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $settings_table WHERE book_id = %d", $book_id ), ARRAY_A );
    }

    $page_width = isset($db_settings['page_width']) ? floatval($db_settings['page_width']) : 21.0;
    $page_height = isset($db_settings['page_height']) ? floatval($db_settings['page_height']) : 29.7;
    
    $cover_settings = get_post_meta( $book_id, '_almaden_cover_settings', true );
    if ( empty($cover_settings) || !is_array($cover_settings) ) {
        return '';
    }

    // Check if there are any layers or front image
    if ( empty($cover_settings['text_layers']) && empty($cover_settings['front_image']) && empty($cover_settings['spread_image']) ) {
        return '';
    }

    $total_pages = get_post_meta( $book_id, '_almaden_total_pages', true );
    $pages = $total_pages ? intval( $total_pages ) : 20;
    if ($pages < 20) $pages = 20;

    $thickness = isset($cover_settings['paper_type']) ? floatval($cover_settings['paper_type']) : 0.06;
    $spineWidthMm = $thickness * $pages;
    
    $frontFlapMm = isset($cover_settings['front_flap_width']) ? floatval($cover_settings['front_flap_width']) : 0;
    $backFlapMm = isset($cover_settings['back_flap_width']) ? floatval($cover_settings['back_flap_width']) : 0;
    
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

    if ($frontFlapMm > 0) $frontFlapPx += $bleedPx; else $frontCoverPx += $bleedPx;
    if ($backFlapMm > 0) $backFlapPx += $bleedPx; else $backCoverPx += $bleedPx;

    $totalSpreadWidthPx = $frontCoverPx + $backCoverPx + $spineWidthPx + $frontFlapPx + $backFlapPx;
    $frontCoverStartPx = $backFlapPx + $backCoverPx + $spineWidthPx;

    $aspectRatio = $frontCoverPx / $actualHeightPx;

    ob_start();
    ?>
    <div class="cover-thumbnail-wrapper w-full bg-white overflow-hidden relative border-b border-gray-200" 
         data-front-cover-px="<?php echo esc_attr($frontCoverPx); ?>"
         data-start-px="<?php echo esc_attr($frontCoverStartPx); ?>"
         style="aspect-ratio: <?php echo round($aspectRatio, 4); ?>;">
        
        <div class="cover-spread-container absolute top-0 left-0" 
             style="width: <?php echo $totalSpreadWidthPx; ?>px; height: <?php echo $actualHeightPx; ?>px; transform-origin: top left; pointer-events: none;">
            
            <?php if ( !empty($cover_settings['spread_image']) ) : ?>
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo esc_url($cover_settings['spread_image']); ?>');"></div>
            <?php else : ?>
                <?php if ( !empty($cover_settings['front_image']) ) : ?>
                    <div class="absolute top-0 bottom-0 bg-cover bg-center" style="left: <?php echo $frontCoverStartPx; ?>px; width: <?php echo $frontCoverPx; ?>px; background-image: url('<?php echo esc_url($cover_settings['front_image']); ?>');"></div>
                <?php endif; ?>
                <?php if ( !empty($cover_settings['back_image']) ) : ?>
                    <div class="absolute top-0 bottom-0 bg-cover bg-center" style="left: <?php echo $backFlapPx; ?>px; width: <?php echo $backCoverPx; ?>px; background-image: url('<?php echo esc_url($cover_settings['back_image']); ?>');"></div>
                <?php endif; ?>
                <?php if ( !empty($cover_settings['spine_image']) ) : ?>
                    <div class="absolute top-0 bottom-0 bg-cover bg-center" style="left: <?php echo ($backFlapPx + $backCoverPx); ?>px; width: <?php echo $spineWidthPx; ?>px; background-image: url('<?php echo esc_url($cover_settings['spine_image']); ?>');"></div>
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

                        $isGradient = isset($layer['isGradient']) && $layer['isGradient'] === 'true';
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
                        $color = isset($layer['color']) ? esc_attr($layer['color']) : '#000000';
                        $fontFamily = isset($layer['fontFamily']) ? esc_attr($layer['fontFamily']) : 'Inter';
                        $textAlign = isset($layer['textAlign']) ? esc_attr($layer['textAlign']) : 'center';
                        $w = isset($layer['width']) && $layer['width'] ? floatval($layer['width']).'px' : 'auto';
                        $h = isset($layer['height']) && $layer['height'] ? floatval($layer['height']).'px' : 'auto';
                        $text = isset($layer['text']) ? esc_html($layer['text']) : '';
                        $hyphens = !empty($layer['hyphens']) ? 'auto' : 'none';

                        $style .= "width: {$w}; height: {$h}; font-size: {$fontSize}px; color: {$color}; font-family: '{$fontFamily}', sans-serif; text-align: {$textAlign}; white-space: pre-wrap; line-height: 1.2; hyphens: {$hyphens}; -webkit-hyphens: {$hyphens};";
                        echo "<div style=\"{$style}\">{$text}</div>";
                    }
                }
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
