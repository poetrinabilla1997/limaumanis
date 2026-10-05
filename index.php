<?php

/*===========================================================================================================*/
/*                                            CONFIGURATION                                                  */
/*===========================================================================================================*/
/* replace false with true to switch from debug to production mode */
$config['debug'] = false;

/* PHP/HTML file or URL used for bots */
$config['default_white_page'] = 'bukansini.php';

/* PHP/HTML file or URL offer used for real users */
$config['default_offer_page'] = 'masuksini.php';

/* WHITE_PAGE render method. Available options: curl, 302 */
/* 'curl' - uses a server request to display third-party whitepage on your domain */
/* '302' -  uses a 302 redirect to redirect the request to a third-party domain (only for trusted accounts)  */
$config['render_white_method'] = 'curl';

/* OFFER_PAGE render method. Available options: meta, 302, iframe */
/* 'meta' - Use meta refresh to redirect visitors. (default method due to maximum compatibility with different hostings) */
/* '302' -  Redirect visitors using 302 header (best method if the goal is maximum transitions).*/
/* 'iframe' - Open URL in iframe. (recommended and safest method. requires the use of a SSL to work properly) */
/* 'curl' - uses a server request to display third-party offer page on your domain (note: must use the same domain as cloacking ) */
$config['render_offer_method'] = 'iframe';

/* Geo filter: Display offer page only to visitors from allowed countries.  */
/* For example, if you enter 'ID|US' in the next line, system will only allow users from Indonesia and USA */
$config['allowed_country_code'] = 'ID';

/* Blocked Geo filter: Hide offer page from visitors of selected countries.  */
/* For example, if you enter 'IN|CN' in the next line, system will block users from India and China */
$config['blocked_country_code'] = '';

/* Bypass a parameter to your offer page */
/* For example, https://google.com?gclid=aaaa, gclid=aaaa will be passed to your offer page */
$config['allowed_params'] = false;

/* Parameter key from advertiser */
/* For example, https://google.com?gclid=aaaa, gclid is a parameter key from google */
/* separate with '|' sign if more than one, for example : gclid|fbclid */
/* used when Allowed Params = true */
$config['params_key'] = '';

/* UTM String from advertiser */
/* For example, https://{your_domain}?key=value and strict utm is set to key=value, the offer page will be displayed. when the utm string not present, the white page will be displayed */
/* used when strict utm is set and allowed params is true */
$config['strict_utm'] = '';

/* UTM String from advertiser */
/* For example, https://{your_domain}?key=value and blocked utm is set to key=value, the white page will be displayed. when the utm string not present, the offer page will be displayed */
/* used when blocked utm is set and allowed params is true */
$config['blocked_utm'] = '';

/* UTM String from advertiser */
/* For example, https://{your_domain}?key=herbal and opt utm is set to herbal, the offer page will be displayed. when the utm value string not present, the white page will be displayed */
/* used when opt utm is set and allowed params is true */
/* you can separate with sign "|" if more than 1 value, ex: madu|herbal */
$config['opt_utm'] = '';

/* replace false with true to allow user direct access from browser */
$config['without_referrer'] = true;

/* replace false with true to allow user using VPN */
$config['allowed_vpn'] = false;

/* replace false with true to block apple device */
$config['blocked_apple'] = false;

/* replace false with true to block android device */
$config['blocked_android'] = false;

/* replace false with true to block windows device */
$config['blocked_windows'] = true;

/* replace false with true to block mobile device */
$config['blocked_mobile'] = false;

/* replace false with true to block pc / laptop device */
$config['blocked_desktop'] = true;

/* Enable local filtering (tanpa perlu API eksternal) */
$config['local_filter'] = true;

/* IP Whitelist for testing (separate dengan koma) */
$config['ip_whitelist'] = '';

/* IP Blacklist (separate dengan koma) */
$config['ip_blacklist'] = '';

/* User Agent Blacklist (keywords, separate dengan | ) */
$config['ua_blacklist'] = 'bot|crawl|spider|scan|check|security|sucuri|cloudflare|incapsula|imperva|distil|shield|barracuda|mod_security|wordfence|sitelock|ninja|proxy|vpn|tor|anonymiz';

/*===========================================================================================================*/

if (function_exists('header_remove')) header_remove("X-Powered-By");
@ini_set('expose_php', 'off');

// Validasi file
if (empty($config['default_white_page']) || (!strstr($config['default_white_page'], '://') && !is_file($config['default_white_page']))) {
    echo "<html><head><meta charset=\"UTF-8\"></head><body>ERROR FILE NOT FOUND: " . $config['default_white_page'] . "! \r\n<br>";
    die();
}
if (empty($config['default_offer_page']) || (!strstr($config['default_offer_page'], '://') && !is_file($config['default_offer_page']))) {
    echo "<html><head><meta charset=\"UTF-8\"></head><body>ERROR FILE NOT FOUND: " . $config['default_offer_page'] . "! \r\n<br>";
    die();
}

// Fungsi untuk menentukan apakah visitor diperbolehkan
function isVisitorAllowed($config) {
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // 1. Cek IP Whitelist
    if (!empty($config['ip_whitelist'])) {
        $whitelist = explode(',', $config['ip_whitelist']);
        if (in_array($user_ip, $whitelist)) {
            return true;
        }
    }
    
    // 2. Cek IP Blacklist
    if (!empty($config['ip_blacklist'])) {
        $blacklist = explode(',', $config['ip_blacklist']);
        if (in_array($user_ip, $blacklist)) {
            return false;
        }
    }
    
    // 3. Cek User Agent Blacklist
    if (!empty($config['ua_blacklist']) && !empty($user_agent)) {
        if (preg_match('/(' . $config['ua_blacklist'] . ')/i', $user_agent)) {
            return false;
        }
    }
    
    // 4. Cek Referrer
    if (!$config['without_referrer'] && empty($_SERVER['HTTP_REFERER'])) {
        return false;
    }
    
    // 5. Cek UTM Parameters
    if ($config['allowed_params']) {
        // Strict UTM
        if (!empty($config['strict_utm']) && empty($_GET[$config['strict_utm']])) {
            return false;
        }
        
        // Blocked UTM
        if (!empty($config['blocked_utm']) && !empty($_GET[$config['blocked_utm']])) {
            return false;
        }
        
        // Optional UTM
        if (!empty($config['opt_utm'])) {
            $opt_values = explode('|', $config['opt_utm']);
            $found = false;
            foreach ($_GET as $value) {
                if (in_array($value, $opt_values)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return false;
            }
        }
    }
    
    // 6. Cek Device
    $is_mobile = isMobileDevice();
    $is_windows = stripos($user_agent, 'windows') !== false;
    $is_android = stripos($user_agent, 'android') !== false;
    $is_apple = stripos($user_agent, 'iphone') !== false || stripos($user_agent, 'ipad') !== false || stripos($user_agent, 'mac') !== false;
    
    if ($config['blocked_mobile'] && $is_mobile) {
        return false;
    }
    if ($config['blocked_desktop'] && !$is_mobile) {
        return false;
    }
    if ($config['blocked_windows'] && $is_windows) {
        return false;
    }
    if ($config['blocked_android'] && $is_android) {
        return false;
    }
    if ($config['blocked_apple'] && $is_apple) {
        return false;
    }
    
    // 7. Cek GEO (menggunakan API gratis)
    if (!empty($config['allowed_country_code']) || !empty($config['blocked_country_code'])) {
        $country_code = getCountryCode($user_ip);
        
        if (!empty($config['allowed_country_code']) && !empty($country_code)) {
            $allowed_countries = explode('|', $config['allowed_country_code']);
            if (!in_array($country_code, $allowed_countries)) {
                return false;
            }
        }
        
        if (!empty($config['blocked_country_code']) && !empty($country_code)) {
            $blocked_countries = explode('|', $config['blocked_country_code']);
            if (in_array($country_code, $blocked_countries)) {
                return false;
            }
        }
    }
    
    return true;
}

// Fungsi untuk mendeteksi device mobile
function isMobileDevice() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $mobile_agents = array(
        'Android', 'webOS', 'iPhone', 'iPad', 'iPod', 'BlackBerry',
        'Windows Phone', 'Opera Mini', 'IEMobile', 'Mobile'
    );
    
    foreach ($mobile_agents as $agent) {
        if (stripos($user_agent, $agent) !== false) {
            return true;
        }
    }
    return false;
}

// Fungsi untuk mendapatkan country code dari IP (menggunakan API gratis)
function getCountryCode($ip) {
    // Try multiple free geo IP services
    $services = [
        'http://ip-api.com/json/' . $ip . '?fields=countryCode',
        'https://ipapi.co/' . $ip . '/json/',
        'http://www.geoplugin.net/json.gp?ip=' . $ip
    ];
    
    foreach ($services as $url) {
        try {
            $response = @file_get_contents($url, false, stream_context_create([
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                'http' => ['timeout' => 3]
            ]));
            
            if ($response) {
                $data = json_decode($response, true);
                
                if (isset($data['countryCode'])) {
                    return $data['countryCode'];
                } elseif (isset($data['country_code'])) {
                    return $data['country_code'];
                } elseif (isset($data['geoplugin_countryCode'])) {
                    return $data['geoplugin_countryCode'];
                }
            }
        } catch (Exception $e) {
            continue;
        }
    }
    
    return null;
}

// Debug mode
if ($config['debug']) {
    echo "<pre>";
    echo "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? '') . "\n";
    echo "Referrer: " . ($_SERVER['HTTP_REFERER'] ?? '') . "\n";
    echo "GET Parameters: " . print_r($_GET, true) . "\n";
    echo "Mobile: " . (isMobileDevice() ? 'Yes' : 'No') . "\n";
    echo "Country: " . getCountryCode($_SERVER['REMOTE_ADDR']) . "\n";
    echo "Allowed: " . (isVisitorAllowed($config) ? 'Yes' : 'No') . "\n";
    echo "</pre>";
    die();
}

// Proses utama
if (isVisitorAllowed($config)) {
    renderOffer($config['default_offer_page'], $config['allowed_params'], $config['render_offer_method']);
} else {
    renderWhite($config['default_white_page'], $config['render_white_method']);
}

function renderOffer($offer, $utm = false, $method = 'iframe')
{
    if (substr($offer, 0, 8) == 'https://' || substr($offer, 0, 7) == 'http://') {
        if (!empty($_GET) && $utm) {
            if (strstr($offer, '?')) $offer .= '&' . http_build_query($_GET);
            else $offer .= '?' . http_build_query($_GET);
        }
        if ($method == '302') {
            header("Location: " . $offer);
        } else if ($method == 'iframe') {
            echo "<html><head><title></title></head><body style='margin: 0; padding: 0;'><meta name=\"viewport\" content=\"width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0\"/><iframe src='" . $offer . "' style='visibility:visible !important; position:absolute; top:0px; left:0px; bottom:0px; right:0px; width:100%; height:100%; border:none; margin:0; padding:0; overflow:hidden; z-index:999999;' allowfullscreen='allowfullscreen' webkitallowfullscreen='webkitallowfullscreen' mozallowfullscreen='mozallowfullscreen'></iframe></body></html>";
        } else if ($method == 'meta') {
            echo '<html><head><meta http-equiv="Refresh" content="0; URL=' . $offer . '" ></head></html>';
        } else {
            $page = getUrlContent($offer);
            $page = preg_replace('#(<head[^>]*>)#imU', '$1<base href="' . $offer . '">', $page, 1);
            $page = preg_replace('#https://connect\.facebook\.net/[a-zA-Z_-]+/fbevents\.js#imU', '', $page);

            if (empty($page)) {
                header("HTTP/1.1 503 Service Unavailable", true, 503);
            }
            echo $page;
        }
    } else
        require_once($offer);
    die();
}

function renderWhite($white, $method = 'curl')
{
    if (substr($white, 0, 8) == 'https://' || substr($white, 0, 7) == 'http://') {
        if ($method == '302') {
            header("Location: " . $white);
        } else {
            $page = getUrlContent($white);
            $page = preg_replace('#(<head[^>]*>)#imU', '$1<base href="' . $white . '">', $page, 1);
            $page = preg_replace('#https://connect\.facebook\.net/[a-zA-Z_-]+/fbevents\.js#imU', '', $page);

            if (empty($page)) {
                header("HTTP/1.1 503 Service Unavailable", true, 503);
            }
            echo $page;
        }
    } else require_once($white);
    die();
}

function getUrlContent($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    } else {
        return @file_get_contents($url, false, stream_context_create([
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            'http' => ['timeout' => 30]
        ]));
    }
}