<?php
class EME_GitHub_Updater {
    private $slug;
    private $plugin_basename;
    private $plugin_dir_path;
    private $github_data;
    private $plugin_file;
    private $github_username;
    private $github_repository;
    private $access_token;
    private $plugin_active;
    private $readme_data = null;

    public function __construct($plugin_file, $github_username, $github_repository, $access_token = '') {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $this->plugin_basename = plugin_basename($plugin_file);
        $this->slug = dirname($this->plugin_basename);
        $this->plugin_file = $plugin_file;
        $this->plugin_dir_path = plugin_dir_path($plugin_file);
        $this->github_username = $github_username;
        $this->github_repository = $github_repository;
        $this->access_token = $access_token;
        $this->plugin_active = is_plugin_active($this->plugin_basename);

        add_filter("update_plugins_github.com", [$this, 'check_update'], 10, 3);
        add_filter('plugins_api', [$this, 'plugin_popup'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'post_install'], 10, 3);
    }

    private function get_repository_info() {
        if (!empty($this->github_data)) {
            return true;
        }
        
        $url = "https://api.github.com/repos/{$this->github_username}/{$this->github_repository}/releases/latest";
        $args = [];
        if ($this->access_token) {
            $args['headers'] = [ 'Authorization' => 'Bearer ' . $this->access_token ];
        }
        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            error_log('GitHub Updater: Failed to fetch release info - ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if (200 !== $response_code) {
            error_log("GitHub Updater: GitHub API returned status {$response_code}");
            return false;
        }

        $github_data = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($github_data) || !isset($github_data['tag_name'])) {
            error_log('GitHub Updater: Invalid release data received');
            return false;
        }

        $this->github_data = $github_data;
        
        return true;
    }

    private function get_readme_data($current_version, $latest_version) {
        if (!is_null($this->readme_data)) {
            return;
        }

        $readme_content = '';
        $plugin_dir = dirname( $this->plugin_file );
        if ($current_version == $latest_version && file_exists("{$plugin_dir}/readme.txt") ) {
            $readme_content = file_get_contents("{$plugin_dir}/readme.txt");
        } else {
            $tag = $this->github_data['tag_name'];
            $url = "https://raw.githubusercontent.com/{$this->github_username}/{$this->github_repository}/refs/tags/{$tag}/readme.txt";
            $args = [];
            if ($this->access_token) {
                $args['headers'] = [ 'Authorization' => 'Bearer ' . $this->access_token ];
            }
            $response = wp_remote_get($url, $args);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $readme_content = wp_remote_retrieve_body($response);
            }
        }
        if (!empty($readme_content)) {
            $this->readme_data = $this->parse_readme($readme_content);
        }
    }

    private function parse_readme($readme_content) {
        $parsed = [
            'sections' => []
        ];
        
        $lines = explode("\n", $readme_content);
        $current_section = '';
        
        foreach ($lines as $line) {
            // Parse headers
            if (preg_match('/^=== (.*?) ===$/', $line, $matches)) {
                $parsed['name'] = trim($matches[1]);
            } elseif (preg_match('/^Requires at least: (.*)$/', $line, $matches)) {
                $parsed['requires'] = trim($matches[1]);
            //} elseif (preg_match('/^Contributors?: (.*)$/', $line, $matches)) {
             //   $parsed['contributors'] = array_map('trim', explode(',', $matches[1]));
            } elseif (preg_match('/^Tested up to: (.*)$/', $line, $matches)) {
                $parsed['tested'] = trim($matches[1]);
            } elseif (preg_match('/^Donate link: (.*)$/', $line, $matches)) {
                $parsed['donate_link'] = trim($matches[1]);
            } elseif (preg_match('/^Requires PHP: (.*)$/', $line, $matches)) {
                $parsed['requires_php'] = trim($matches[1]);
            } elseif (preg_match('/^Stable tag: (.*)$/', $line, $matches)) {
                $parsed['stable_tag'] = trim($matches[1]);
            } elseif (preg_match('/^== (.*?) ==$/', $line, $matches)) {
                $current_section = strtolower(str_replace(' ', '_', trim($matches[1])));
                if (!isset($parsed['sections'][$current_section])) {
                    $parsed['sections'][$current_section] = '';
                }
            } elseif (empty($parsed['short_description']) && !empty(trim($line)) && $current_section == '') {
                // First non-empty line after headers is short description
                $parsed['short_description'] = trim($line);
            } elseif ($current_section && !empty(trim($line)) ) {
                // Add to current section
                $parsed['sections'][$current_section] .= $line . "\n";
            }
        }
        
        // Clean up sections
        foreach ($parsed['sections'] as $key => $content) {
            $parsed['sections'][$key] = trim($content);
        }
        
        return $parsed;
    }

    public function check_update($update, $plugin_data, $plugin_file) {
        // Only respond to our plugin
        if ($this->slug !== dirname(plugin_basename($plugin_file))) {
            return $update;
        }
        
        // If update is already set, return it
        if ($update !== false) {
            return $update;
        }

        if (!$this->get_repository_info()) {
            return false;
        }

        $current_version = $plugin_data['Version'];
        $latest_version = ltrim($this->github_data['tag_name'], 'v');

        // WP does the version compare, check_update always needs to return an object
        //if (!version_compare($latest_version, $current_version, '>')) {
         //   return $update;
       // }

        // Create update object
        // id and plugin are being set fixed in wp-includes/update.php after the filter update_plugins_{$hostname}
        $update = new stdClass();
        $update->slug = $this->slug;
        $update->version = $current_version; // version just needs to be present, but the value is ignored if new_version is also present
        $update->new_version = $latest_version; // if this is not present, then WP will copy version into new_version
        $update->url = $plugin_data['PluginURI'];
        
        // Get package URL
        if (isset($this->github_data['assets'][0]['browser_download_url'])) {
            $update->package = $this->github_data['assets'][0]['browser_download_url'];
        } else {
            $update->package = $this->github_data['zipball_url'];
        }
        
        // Add access token if needed (for private repos)
        if ($this->access_token) {
            $update->package = add_query_arg(
                ['access_token' => $this->access_token],
                $update->package
            );
        }

        /* the rest is not really needed and by not asking it, we skip on a github call to readme.txt too
        // Get readme data for version requirements
        $this->get_readme_data();

        $update->tested = !empty($this->readme_data['tested']) ? $this->readme_data['tested'] : $this->get_tested_wp_version();
        $update->requires_php = !empty($this->readme_data['requires_php']) ? $this->readme_data['requires_php'] : $this->get_requires_php($plugin_data);
        $update->requires = !empty($this->readme_data['requires']) ? $this->readme_data['requires'] : $this->get_requires_wp_version($plugin_data);
        $update->donate_link = !empty($this->readme_data['donate_link']) ? $this->readme_data['donate_link'] : '';
         */

        $update->tested = $this->get_tested_wp_version();
        // Add icons and banners for update notification
        $update->icons = $this->get_icons();
        $update->banners = $this->get_banners();

        return $update;
    }

    public function plugin_popup($result, $action, $args) {
        if ('plugin_information' !== $action) {
            return $result;
        }

        // Check if this is our plugin
        if (!isset($args->slug) || $args->slug !== $this->slug) {
            return $result;
        }

        if (!$this->get_repository_info()) {
            return $result;
        }

        // Get readme and plugin data
        $plugin_data = get_plugin_data($this->plugin_file);
        $current_version = $plugin_data['Version'];
        $latest_version = ltrim($this->github_data['tag_name'], 'v');
        $this->get_readme_data($current_version, $latest_version);
        
        $plugin_info = new stdClass();
        $plugin_info->name = !empty($this->readme_data['name']) ? $this->readme_data['name'] : $plugin_data['Name'];
        $plugin_info->slug = $this->slug;
        //$plugin_info->plugin = $this->plugin_file;
        $plugin_info->version = $latest_version;
        $plugin_info->author = $plugin_data['Author'];
        $plugin_info->requires = !empty($this->readme_data['requires']) ? $this->readme_data['requires'] : $this->get_requires_wp_version($plugin_data);
        //$plugin_info->tested = !empty($this->readme_data['tested']) ? $this->readme_data['tested'] : $this->get_tested_wp_version();
        $plugin_info->tested = $this->get_tested_wp_version();
        $plugin_info->requires_php = !empty($this->readme_data['requires_php']) ? $this->readme_data['requires_php'] : $this->get_requires_php($plugin_data);
        $plugin_info->donate_link = !empty($this->readme_data['donate_link']) ? $this->readme_data['donate_link'] : '';
        $plugin_info->homepage = $plugin_data['PluginURI'];
        $plugin_info->last_updated = $this->github_data['published_at'];
        
        // Download link
        if (isset($this->github_data['assets'][0]['browser_download_url'])) {
            $plugin_info->download_link = $this->github_data['assets'][0]['browser_download_url'];
        } else {
            $plugin_info->download_link = $this->github_data['zipball_url'];
        }
        
        if ($this->access_token) {
            $plugin_info->download_link = add_query_arg(
                ['access_token' => $this->access_token],
                $plugin_info->download_link
            );
        }
        
        // Build sections from readme
        $plugin_info->sections = [];
        
        foreach ($this->readme_data['sections'] as $key => $value) {
            $plugin_info->sections[$key] = $this->parse_markdown($value);
        }

        // Add banners and icons
        $plugin_info->banners = $this->get_banners();
        $plugin_info->icons = $this->get_icons();

        if (isset($plugin_info->sections['screenshots'])) {
            $asset_url = plugin_dir_url( $this->plugin_file )."assets/";
            $asset_dir = $this->plugin_dir_path."/assets/";
            $res = '<ol>';
            preg_match_all('|<li>(.*?)</li>|s', $plugin_info->sections['screenshots'], $matches);
            if (!empty($matches[1])) {
                $count = 1;
                foreach ($matches[1] as $description) {
                    if (file_exists($asset_dir."screenshot-$count.png"))
                        $image = $asset_url."screenshot-$count.png";
                    elseif (file_exists($asset_dir."screenshot-$count.gif"))
                        $image = $asset_url."screenshot-$count.gif";
                    else
                        $image = "https://raw.githubusercontent.com/{$this->github_username}/{$this->github_repository}/refs/tags/{$this->github_data['tag_name']}/assets/screenshot-$count.gif";
                    $escaped_image = esc_url($image);
                    $safe_description = wp_kses_post($description);
                    $tmp = "<li><a href='{$escaped_image}'><img src='{$escaped_image}'></a><p>{$safe_description}</p></li>";
                    $count++;
                    $res .= $tmp;
                }
            }
            $res .= '</ol>';
            $plugin_info->sections['screenshots'] = $res;
        }

        return $plugin_info;
    }

    private function get_banners() {
        $banners = [
            'low' => '',
            'high' => ''
        ];
        
        $low_banner = $this->get_banner_url('low');
        $high_banner = $this->get_banner_url('high');
        
        if ($low_banner) {
            $banners['low'] = $low_banner;
        }
        
        if ($high_banner) {
            $banners['high'] = $high_banner;
        }
        
        // Return empty array if no banners found
        return array_filter($banners);
    }
    
    private function get_icons() {
        $icons = [];
        
        $icon_1x = $this->get_icon_url('1x');
        $icon_2x = $this->get_icon_url('2x');
        $icon_svg = $this->get_icon_url('svg');
        
        if ($icon_1x) {
            $icons['1x'] = $icon_1x;
        }
        
        if ($icon_2x) {
            $icons['2x'] = $icon_2x;
        }
        
        if ($icon_svg) {
            $icons['svg'] = $icon_svg;
            $icons['default'] = $icon_svg; // WordPress uses 'default' key
        } elseif ($icon_1x) {
            $icons['default'] = $icon_1x;
        }
        
        return $icons;
    }

    private function get_banner_url($type = 'low') {
        $banner_filename = $type === 'high' ? 'banner-1544x500.png' : 'banner-772x250.png';

        // Try different file extensions
        $extensions = ['.png', '.jpg', '.jpeg', '.gif', '.svg'];

        foreach ($extensions as $ext) {
            $filename = str_replace('.png', $ext, $banner_filename);
            $local_path = $this->plugin_dir_path . 'assets/' . $filename;

            if (file_exists($local_path)) {
                $url = plugins_url('assets/' . $filename, $this->plugin_file);
                return $url;
            }
        }

        return '';
    }

    private function get_icon_url($type = '1x') {
        $sizes = [
            '1x' => ['128x128', '128'],
            '2x' => ['256x256', '256'],
            'svg' => ['svg']
        ];

        $extensions = ['.png', '.jpg', '.jpeg', '.gif', '.svg'];

        foreach ($sizes[$type] as $size) {
            foreach ($extensions as $ext) {
                $filenames = [
                    "icon-{$size}{$ext}",
                    "icon{$ext}",
                    "plugin-icon-{$size}{$ext}",
                ];

                foreach ($filenames as $filename) {
                    // Check in assets folder first (most common)
                    $local_path = $this->plugin_dir_path . 'assets/' . $filename;
                    if (file_exists($local_path)) {
                        $url = plugins_url('assets/' . $filename, $this->plugin_file);
                        return $url;
                    }

                    // Check in plugin root
                    $local_path = $this->plugin_dir_path . $filename;
                    if (file_exists($local_path)) {
                        $url = plugins_url($filename, $this->plugin_file);
                        return $url;
                    }
                }
            }
        }

        return '';
    }

    public function post_install($true, $hook_extra, $result) {
        // Check if this is our plugin
        if (!isset($hook_extra['plugin']) || dirname($hook_extra['plugin']) !== $this->slug) {
            return $true;
        }
        
        global $wp_filesystem;
        
        // Check if source and destination are different
        if ($result['destination'] !== $this->plugin_dir_path) {
            if ($wp_filesystem->move($result['destination'], $this->plugin_dir_path)) {
                $result['destination'] = $this->plugin_dir_path;
            }
        }
        
        // Reactivate if it was active
        if ($this->plugin_active) {
            activate_plugin($this->plugin_basename, '', is_multisite());
        }
        
        return $result;
    }

    private function get_tested_wp_version() {
        return get_bloginfo('version');
    }

    private function get_requires_wp_version($plugin_data) {
        return isset($plugin_data['RequiresWP']) ? $plugin_data['RequiresWP'] : '5.0';
    }

    private function get_requires_php($plugin_data) {
        return isset($plugin_data['RequiresPHP']) ? $plugin_data['RequiresPHP'] : '7.0';
    }

    private function parse_markdown($markdown) {
        if (empty($markdown)) {
            return '';
        }

        // Split by lines to process properly
        $lines = explode("\n", $markdown);
        $processed_lines = [];
        $in_list = false;
        $list_items = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Handle headers first (they break lists)
            if (preg_match('/^= (.+) =$/', $trimmed, $matches)) {
                // If we were in a list, close it first
                if ($in_list && !empty($list_items)) {
                    $processed_lines[] = '<ul>' . implode('', $list_items) . '</ul>';
                    $list_items = [];
                    $in_list = false;
                }
                $processed_lines[] = '<h4>' . esc_html(trim($matches[1])) . '</h4>';
            }
            // Handle other headers
            elseif (preg_match('/^# (.+)$/', $trimmed, $matches)) {
                if ($in_list && !empty($list_items)) {
                    $processed_lines[] = '<ul>' . implode('', $list_items) . '</ul>';
                    $list_items = [];
                    $in_list = false;
                }
                $processed_lines[] = '<h1>' . esc_html(trim($matches[1])) . '</h1>';
            }
            elseif (preg_match('/^## (.+)$/', $trimmed, $matches)) {
                if ($in_list && !empty($list_items)) {
                    $processed_lines[] = '<ul>' . implode('', $list_items) . '</ul>';
                    $list_items = [];
                    $in_list = false;
                }
                $processed_lines[] = '<h2>' . esc_html(trim($matches[1])) . '</h2>';
            }
            elseif (preg_match('/^### (.+)$/', $trimmed, $matches)) {
                if ($in_list && !empty($list_items)) {
                    $processed_lines[] = '<ul>' . implode('', $list_items) . '</ul>';
                    $list_items = [];
                    $in_list = false;
                }
                $processed_lines[] = '<h3>' . esc_html(trim($matches[1])) . '</h3>';
            }
            // Handle list items
            elseif (preg_match('/^\* (.+)$/', $trimmed, $matches)) {
                $in_list = true;
                $list_content = $this->parse_inline_markdown(trim($matches[1]));
                $list_items[] = '<li>' . $list_content . '</li>';
            }
            // Handle numbered lists
            elseif (preg_match('/^\d+\. (.+)$/', $trimmed, $matches)) {
                $in_list = true;
                $list_content = $this->parse_inline_markdown(trim($matches[1]));
                $list_items[] = '<li>' . $list_content . '</li>';
            }
            // Empty line - close list if we were in one
            // In parse_readme we already remove empty lines so this is not really needed
            elseif (empty($trimmed)) {
                if ($in_list && !empty($list_items)) {
                    $processed_lines[] = '<ul>' . implode('', $list_items) . '</ul>';
                    $list_items = [];
                    $in_list = false;
                }
                $processed_lines[] = '';
            }
            // Regular text
            else {
                // Close list if we were in one and now have regular text
                if ($in_list && !empty($list_items)) {
                    $processed_lines[] = '<ul>' . implode('', $list_items) . '</ul>';
                    $list_items = [];
                    $in_list = false;
                }
                $processed_lines[] = '<p>' . $this->parse_inline_markdown($trimmed) . '</p>';
            }
        }

        // Close any open list at the end
        if ($in_list && !empty($list_items)) {
            $processed_lines[] = '<ul>' . implode('', $list_items) . '</ul>';
        }

        // Combine lines
        $html = implode("\n", $processed_lines);
        // Security: Allow only safe HTML
        return wp_kses_post($html);
    }

    private function parse_inline_markdown($text) {
        // Bold: **bold** or __bold__
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.*?)__/', '<strong>$1</strong>', $text);

        // Italic: *italic* or _italic_
        $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/_(.*?)_/', '<em>$1</em>', $text);

        // Code: `code`
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

        // break: 2 spaces at the end
        $text = preg_replace('/(.*?)  $/', '$1<br>', $text);        

        // Links: [text](url) with URL escaping
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            function($matches) {
                return '<a href="' . esc_url($matches[2]) . '" target="_blank" rel="noopener">' . $matches[1] . '</a>';
            },
            $text
        );

        return $text;
    }
}
