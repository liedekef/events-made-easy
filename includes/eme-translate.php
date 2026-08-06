<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_load_textdomain() {
        $domain = 'events-made-easy';
        $locale = determine_locale();
        $moFile = $domain . '-' . $locale . '.mo';
        $path = EME_PLUGIN_DIR . '/langs';
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
	// check for a known multilingual plugin first: its own configured mode is authoritative
	// and shouldn't be overridden by a stray/leftover ?lang= on the current request
	$url_mode = 0;
    if ( function_exists( 'pll_current_language' ) ) {
        $url_mode = 2;
    } elseif ( function_exists( 'qtranxf_getLanguage' ) ) {
        $url_mode = get_option( 'qtranslate_url_mode' );
        if ( empty( $url_mode ) ) {
            $url_mode = 2;
        }
    } elseif ( isset( $_GET['lang'] ) ) {
        $url_mode = 1;
	}
	if ( empty( $url_mode ) ) {
		// same reasoning as in eme_uri_add_lang(): anchor on the raw, unfiltered home url so a multisite
		// subdir base path can't be mistaken for an already-appended language segment
		$lang_regex = apply_filters( 'eme_language_regex', EME_LANGUAGE_REGEX );
		$url        = eme_current_page_url();
		$home_url   = preg_quote( untrailingslashit( set_url_scheme( get_option( 'home' ) ) ), '/' );
		if ( preg_match( "/$home_url\/($lang_regex)\//", $url ) ) {
			$url_mode = 2;
		}
	}
	wp_cache_set( 'eme_url_mode', $url_mode, '', 10 );
	return $url_mode;
}

// swap the 'lang' query arg on an already-built url (removing any existing one first so we never end up
// with it twice).
function eme_add_lang_query_arg( $the_link, $language ) {
	if ( empty( $language ) ) {
		return $the_link;
	}
	$the_link = remove_query_arg( 'lang', $the_link );
	$the_link = add_query_arg( [ 'lang' => $language ], $the_link );
	return $the_link;
}

function eme_uri_add_lang( $name, $lang ) {
	$the_link = home_url();
	// some plugins add the lang info to the home_url, remove it so we don't get into trouble or add it twice
	if ( ! empty( $lang ) ) {
		$url_mode = eme_lang_url_mode();
		if ( $url_mode == 2 ) {
			// don't try to guess/strip a possibly already-appended language segment from the (filtered)
			// home_url(): on a multisite path install the site's own base path (e.g. /etk/) can look just
			// like a language slug and get stripped by mistake. Read the raw, unfiltered home url straight
			// from the option instead: translation plugins only add the language via the 'home_url' filter,
			// they never touch the stored option value, so this is reliable regardless of which plugin (or
			// none) is active, and regardless of request context (also safe to use from cron/mail code).
			$the_link = set_url_scheme( get_option( 'home' ) );
			$the_link = trailingslashit( $the_link ) . "$lang/" . user_trailingslashit( $name );
		} elseif ( $url_mode == 1 ) {
			$the_link = trailingslashit( remove_query_arg( 'lang', $the_link ) );
			$the_link = $the_link . user_trailingslashit( $name );
			$the_link = eme_add_lang_query_arg( $the_link, $lang );
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

function eme_trans_esc_html( $value, $lang = '' ) {
	return esc_html( eme_translate( $value, $lang ) );
}

function eme_translate( $value, $lang = '', $use_wp_trans = 1 ) {
	if ( empty( $value ) ) {
		return $value;
	}
	$translated = $value;
    if ( function_exists( 'pll_translate_string' ) && function_exists( 'pll__' ) ) {
        // pll language notation is different from what qtrans (and eme) support, so lets also translate the eme language tags
        $value = eme_translate_string( $value, $lang );
        if ( empty( $lang ) ) {
            $translated = pll__( $value );
        } else {
            $translated = pll_translate_string( $value, $lang );
        }
    } elseif ( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) && function_exists( 'qtranxf_use' ) ) {
        if ( empty( $lang ) ) {
            $translated = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $value );
        } else {
            $translated = qtranxf_use( $lang, $value );
        }   
    }
	if ( $translated != $value ) {
		return $translated;
	} else { 
		return eme_translate_string( $value, $lang );
	}
}

function eme_translate_string( $text, $lang = '' ) {
	if ( empty( $text ) ) {
		return $text;
	}
	if ( empty( $lang ) ) {
		$lang = eme_detect_lang();
	}
	$languages = eme_detect_used_languages( $text );
	if ( empty( $languages ) ) {
        return $text;
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
	$lang_regex = apply_filters( 'eme_language_regex', EME_LANGUAGE_REGEX );

	$languages = [];
	if ( preg_match_all( "/\[:($lang_regex?)\]/", $text, $matches ) ) {
		$languages = array_unique( $matches[1] );
	} elseif ( preg_match_all( "/\{:($lang_regex?)\}/", $text, $matches ) ) {
		$languages = array_unique( $matches[1] );
	}
	return $languages;
}

function eme_split_language_blocks( $text, $languages ) {
	$lang_regex = apply_filters( 'eme_language_regex', EME_LANGUAGE_REGEX );

	$result = [];
	foreach ( $languages as $language ) {
		$result[ $language ] = '';
	}
	$current_language = false;
	$split_regex      = "#(\[:$lang_regex\]|\[:\]|\{:$lang_regex\}|\{:\})#ism";
	$blocks           = preg_split( $split_regex, $text, - 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
	foreach ( $blocks as $block ) {
		// detect tags
		if ( preg_match( "#^\[:($lang_regex)]$#ism", $block, $matches ) ) {
			$current_language = $matches[1];
			continue;
		} elseif ( preg_match( "#^{:($lang_regex)}$#ism", $block, $matches ) ) {
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


