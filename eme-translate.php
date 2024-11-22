<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_load_textdomain() {
        $domain = 'events-made-easy';
        $locale = determine_locale();
        $moFile = $domain . '-' . $locale . '.mo';
        $path = eme_plugin_dir() . '/langs';
        if ( file_exists( $path ) ) {
            // the locale is optional, but we already have the info, so it makes the function just a bit faster
            load_textdomain($domain, $path . '/' . $moFile, $locale);
        }
}

function eme_detect_lang() {
	$language = wp_cache_get( 'eme_language' );
	if ( $language === false ) {
        if ( ! empty( $_GET['lang'] ) ) {
            $language = eme_sanitize_request( $_GET['lang'] );
        } else {
            $language = substr( determine_locale(), 0, 2 );
        }
        // no spaces allowed, so remove everything after the first space
        $language = preg_replace( '/\s+.*/', '', $language );
        wp_cache_set( 'eme_language', $language, '', 10 );
    }
	return $language;
}

function eme_lang_url_mode() {
	$url_mode = wp_cache_get( 'eme_url_mode' );
	if ( $url_mode !== false ) {
		return $url_mode;
	}

	// should be an option
	$url_mode = 0;
	if ( isset( $_GET['lang'] ) ) {
		$url_mode = 1;
	} elseif ( function_exists( 'mqtranslate_conf' ) ) {
		// only some functions in mqtrans are different, but the options are named the same as for qtranslate
		$url_mode = get_option( 'mqtranslate_url_mode' );
	} elseif ( function_exists( 'qtrans_getLanguage' ) ) {
		$url_mode = get_option( 'qtranslate_url_mode' );
	} elseif ( function_exists( 'ppqtrans_getLanguage' ) ) {
		$url_mode = get_option( 'pqtranslate_url_mode' );
	} elseif ( function_exists( 'qtranxf_getLanguage' ) ) {
		$url_mode = get_option( 'qtranslate_url_mode' );
		if ( empty( $url_mode ) ) {
			$url_mode = 2;
		}
	} elseif ( function_exists( 'pll_current_language' ) ) {
		$url_mode = 2;
	}
	if ( empty( $url_mode ) ) {
		$lang_code = apply_filters( 'eme_language_regex', EME_LANGUAGE_REGEX );
		$url       = eme_current_page_url();
		$home_url  = preg_quote( preg_replace( "/\/$lang_code\/?$/", '', home_url() ), '/' );
		if ( preg_match( "/$home_url\/($lang_code)\//", $url ) ) {
			$url_mode = 2;
		}
	}
	wp_cache_set( 'eme_url_mode', $url_mode, '', 10 );
	return $url_mode;
}

function eme_uri_add_lang( $name, $lang ) {
	$the_link = home_url();
	// some plugins add the lang info to the home_url, remove it so we don't get into trouble or add it twice
	if ( ! empty( $lang ) ) {
		$url_mode = eme_lang_url_mode();
		if ( $url_mode == 2 ) {
			$lang_code = apply_filters( 'eme_language_regex', EME_LANGUAGE_REGEX );
			$the_link  = preg_replace( "/\/$lang_code\/?$/", '', $the_link );
			$the_link  = trailingslashit( $the_link ) . "$lang/" . user_trailingslashit( $name );
		} elseif ( $url_mode == 1 ) {
			$the_link = trailingslashit( remove_query_arg( 'lang', $the_link ) );
			$the_link = $the_link . user_trailingslashit( $name );
			$the_link = add_query_arg( [ 'lang' => $lang ], $the_link );
		} else {
			// url_mode is 0, then we don't add the lang and let wp do it
			$the_link = trailingslashit( $the_link ) . user_trailingslashit( $name );
		}
	} else {
		$the_link = trailingslashit( $the_link ) . user_trailingslashit( $name );
	}
	return $the_link;
}

//backwards compat
function eme_trans_sanitize_html( $value, $lang = '' ) {
	return eme_trans_esc_html( $value, $lang );
}

function eme_trans_nowptrans_esc_html( $value, $lang = '' ) {
	return eme_trans_esc_html( $value, $lang, 0 );
}

function eme_trans_esc_html( $value, $lang = '', $use_wp_trans = 1 ) {
	return eme_esc_html( eme_translate( $value, $lang, $use_wp_trans ) );
}

function eme_translate_nowptrans( $value, $lang = '' ) {
	return eme_translate( $value, $lang, 0 );
}

function eme_translate( $value, $lang = '', $use_wp_trans = 1 ) {
	$translated = $value;
	if ( function_exists( 'qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) && function_exists( 'qtrans_use' ) ) {
		if ( empty( $lang ) ) {
			$translated = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage( $value );
		} else {
			$translated = qtrans_use( $lang, $value );
		}
	} elseif ( function_exists( 'ppqtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) && function_exists( 'ppqtrans_use' ) ) {
		if ( empty( $lang ) ) {
			$translated = ppqtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage( $value );
		} else {
			$translated = ppqtrans_use( $lang, $value );
		}
	} elseif ( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) && function_exists( 'qtranxf_use' ) ) {
		if ( empty( $lang ) ) {
			$translated = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $value );
		} else {
			$translated = qtranxf_use( $lang, $value );
		}
	} elseif ( function_exists( 'pll_translate_string' ) && function_exists( 'pll__' ) ) {
		// pll language notation is different from what qtrans (and eme) support, so lets also translate the eme language tags
		$value = eme_translate_string( $value, $lang, $use_wp_trans );
		if ( empty( $lang ) ) {
			$translated = pll__( $value );
		} else {
			$translated = pll_translate_string( $value, $lang );
		}
	}
	if ( $translated != $value ) {
		return $translated;
	} else { 
		return eme_translate_string( $value, $lang, $use_wp_trans );
	}
}

function eme_translate_string_nowptrans( $value, $lang = '' ) {
	return eme_translate_string( $value, $lang, 0 );
}

function eme_translate_string( $text, $lang = '', $use_wp_trans = 1 ) {
	if ( empty( $text ) ) {
		return $text;
	}
	if ( empty( $lang ) ) {
		$lang = eme_detect_lang();
	}
	$languages = eme_detect_used_languages( $text );
	if ( empty( $languages ) ) {
		if ( $use_wp_trans ) {
			// no language is encoded in the $text (most frequent case), then run it through wp trans and be done with it
			return __( $text, 'events-made-easy' );
		} else {
			return $text;
		}
	}
	$content   = eme_split_language_blocks( $text, $languages );
	$languages = array_keys( $content );
	if ( empty( $lang ) ) {
		// no language? then return the first one
		$lang = $languages[0];
	}
	if ( isset( $content[ $lang ] ) ) {
		return $content[ $lang ];
	} else {
		return $content[ $languages[0] ];
	}
}

function eme_detect_used_languages( $text ) {
	$lang_code = apply_filters( 'eme_language_regex', EME_LANGUAGE_REGEX );

	$languages = [];
	if ( preg_match_all( "/\[:($lang_code?)\]/", $text, $matches ) ) {
		$languages = array_unique( $matches[1] );
	} elseif ( preg_match_all( "/\{:($lang_code?)\}/", $text, $matches ) ) {
		$languages = array_unique( $matches[1] );
	}
	return $languages;
}

function eme_split_language_blocks( $text, $languages ) {
	$lang_code = apply_filters( 'eme_language_regex', EME_LANGUAGE_REGEX );

	$result = [];
	foreach ( $languages as $language ) {
		$result[ $language ] = '';
	}
	$current_language = false;
	$split_regex      = "#(\[:$lang_code\]|\[:\]|\{:$lang_code\}|\{:\})#ism";
	$blocks           = preg_split( $split_regex, $text, - 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
	foreach ( $blocks as $block ) {
		// detect tags
		if ( preg_match( "#^\[:($lang_code)]$#ism", $block, $matches ) ) {
			$current_language = $matches[1];
			continue;
		} elseif ( preg_match( "#^{:($lang_code)}$#ism", $block, $matches ) ) {
			$current_language = $matches[1];
			continue;
		}
		switch ( $block ) {
			case '[:]':
			case '{:}':
				$current_language = false;
				break;
			default:
				// correctly categorize text block
				if ( $current_language ) {
					if ( ! isset( $result[ $current_language ] ) ) {
						$result[ $current_language ] = '';
					}
					$result[ $current_language ] .= $block;
					$current_language             = false;
				} else {
					// this catches the case for text outside a translation part
					foreach ( $languages as $language ) {
						$result[ $language ] .= $block;
					}
				}
				break;
		}
	}
	return $result;
}


