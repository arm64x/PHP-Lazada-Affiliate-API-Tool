<?php
/**
 * Lazada Affiliate API - Web Interface v1.0
 * Support inputType = url + Auto clean tracking params
 * Auto-load ALL data + Client-side Pagination
 * + Daily Chart Statistics
 * Lê Tí - CodeVN
 */

session_start();

// ==============================================================================
// CLASS LAZADA AFFILIATE API
// ==============================================================================

class LazadaAffiliateAPI
{
    private $appKey;
    private $appSecret;
    private $userToken;
    private $baseUrl;
    private $signMethod = 'sha256';
    private $sdkVersion = 'lazop-sdk-php-affiliate-1.0';

    // Tracking parameters cần loại bỏ
    private static $trackingParams = [
        'trafficFrom',
        'laz_trackid',
        'mkttid',
        'exlaz',
        'spm',
        'scm',
        'from',
        'clickTrackInfo',
        'search',
        'mp',
        'c',
        'abbucket',
        'aff_trace_key',
        'aff_platform',
        'aff_request_id',
        'sk',
        'utparam',
    ];

    public function __construct($config)
    {
        $this->appKey = $config['app_key'];
        $this->appSecret = $config['app_secret'];
        $this->userToken = $config['user_token'];
        $this->baseUrl = rtrim($config['base_url'], '/');
    }

    /**
     * Lấy tracking link bằng Product ID (inputType = productId)
     */
    public function getTrackingLinkByProductId($productId, $mmCampaignId = null, $dmInviteId = null, $subAffId = null, $subIds = [])
    {
        $params = [
            'userToken' => $this->userToken,
            'inputType' => 'productId',
            'inputValue' => $productId,
        ];

        if ($mmCampaignId !== null && $mmCampaignId !== '') {
            $params['mmCampaignId'] = $mmCampaignId;
        }

        if ($dmInviteId !== null && $dmInviteId !== '') {
            $params['dmInviteId'] = $dmInviteId;
        }

        if ($subAffId !== null && $subAffId !== '') {
            $params['subAffId'] = $subAffId;
        }

        // Thêm sub IDs nếu có
        for ($i = 1; $i <= 6; $i++) {
            if (isset($subIds["subId$i"]) && $subIds["subId$i"] !== '') {
                $params["subId$i"] = $subIds["subId$i"];
            }
        }

        return $this->callAPI('/marketing/getlink', $params);
    }

    /**
     * Lấy tracking link bằng URL (inputType = url)
     */
    public function getTrackingLinkByUrl($url, $mmCampaignId = null, $dmInviteId = null, $subAffId = null, $subIds = [])
    {
        // Clean URL trước khi gửi
        $cleanedUrl = self::cleanTrackingParams($url);

        $params = [
            'userToken' => $this->userToken,
            'inputType' => 'url',
            'inputValue' => $cleanedUrl,
        ];

        if ($mmCampaignId !== null && $mmCampaignId !== '') {
            $params['mmCampaignId'] = $mmCampaignId;
        }

        if ($dmInviteId !== null && $dmInviteId !== '') {
            $params['dmInviteId'] = $dmInviteId;
        }

        if ($subAffId !== null && $subAffId !== '') {
            $params['subAffId'] = $subAffId;
        }

        // Thêm sub IDs nếu có
        for ($i = 1; $i <= 6; $i++) {
            if (isset($subIds["subId$i"]) && $subIds["subId$i"] !== '') {
                $params["subId$i"] = $subIds["subId$i"];
            }
        }

        return $this->callAPI('/marketing/getlink', $params);
    }

    /**
     * Lấy tracking link bằng Offer ID (inputType = offerId)
     */
    public function getTrackingLinkByOfferId($offerId, $mmCampaignId = null, $dmInviteId = null, $subAffId = null, $subIds = [])
    {
        $params = [
            'userToken' => $this->userToken,
            'inputType' => 'offerId',
            'inputValue' => $offerId,
        ];

        if ($mmCampaignId !== null && $mmCampaignId !== '') {
            $params['mmCampaignId'] = $mmCampaignId;
        }

        if ($dmInviteId !== null && $dmInviteId !== '') {
            $params['dmInviteId'] = $dmInviteId;
        }

        if ($subAffId !== null && $subAffId !== '') {
            $params['subAffId'] = $subAffId;
        }

        // Thêm sub IDs nếu có
        for ($i = 1; $i <= 6; $i++) {
            if (isset($subIds["subId$i"]) && $subIds["subId$i"] !== '') {
                $params["subId$i"] = $subIds["subId$i"];
            }
        }

        return $this->callAPI('/marketing/getlink', $params);
    }

    public function getConversionReport($dateStart, $dateEnd, $limit = 100, $page = 1, $offerId = null, $mmPartnerFlag = false)
    {
        $params = [
            'userToken' => $this->userToken,
            'dateStart' => $dateStart,
            'dateEnd'   => $dateEnd,
            'limit'     => $limit,
            'page'      => $page,
            'mmPartnerFlag' => $mmPartnerFlag ? 'true' : 'false',
        ];

        if ($offerId !== null && $offerId !== '') {
            $params['offerId'] = $offerId;
        }

        return $this->callAPI('/marketing/conversion/report', $params);
    }

    private function callAPI($apiPath, $apiParams)
    {
        $sysParams = [
            'app_key' => $this->appKey,
            'sign_method' => $this->signMethod,
            'timestamp' => $this->getTimestamp(),
            'partner_id' => $this->sdkVersion,
        ];

        $allParams = array_merge($apiParams, $sysParams);
        $sysParams['sign'] = $this->generateSign($apiPath, $allParams);

        $url = $this->baseUrl . '/rest' . $apiPath . '?' . http_build_query($sysParams);

        return $this->httpRequest($url, $apiParams, 'GET');
    }

    private function generateSign($apiPath, $params)
    {
        ksort($params);

        $stringToBeSigned = $apiPath;
        foreach ($params as $key => $value) {
            $stringToBeSigned .= $key . $value;
        }

        return strtoupper(hash_hmac('sha256', $stringToBeSigned, $this->appSecret));
    }

    private function getTimestamp()
    {
        return round(microtime(true) * 1000);
    }

    private function httpRequest($url, $apiParams = null, $method = 'GET')
    {
        $ch = curl_init();

        if ($method === 'GET' && $apiParams) {
            $url .= '&' . http_build_query($apiParams);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->sdkVersion);

        if ($method === 'POST' && $apiParams) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiParams));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => 'CURL Error: ' . $error,
                'http_code' => $httpCode,
                'url' => $finalUrl,
            ];
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'JSON Decode Error: ' . json_last_error_msg(),
                'raw_response' => $response,
                'url' => $finalUrl,
            ];
        }

        if (isset($decoded['code']) && $decoded['code'] !== '0' && $decoded['code'] !== 0) {
            return [
                'success' => false,
                'error' => $decoded['message'] ?? 'API Error',
                'error_code' => $decoded['code'],
                'data' => $decoded,
                'url' => $finalUrl,
            ];
        }

        return [
            'success' => true,
            'data' => $decoded,
            'http_code' => $httpCode,
            'url' => $finalUrl,
        ];
    }

    private static function followUrl($url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
            ]
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        
        if ($error) {
            return null;
        }
        
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        return [
            'finalUrl' => $finalUrl,
            'headers' => $headers,
            'body' => $body,
        ];
    }

    /**
     * Loại bỏ các tham số tracking khỏi URL
     */
    public static function cleanTrackingParams($url)
    {
        if (empty($url)) {
            return $url;
        }

        $parsedUrl = parse_url($url);
        
        if (!isset($parsedUrl['host'])) {
            return $url;
        }

        // Parse query string
        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }

        // Loại bỏ các tracking params
        $paramsToRemove = self::$trackingParams;
        
        // Thêm các pattern khác
        foreach ($queryParams as $key => $value) {
            // Loại bỏ sub_id1-6 nếu có giá trị tracking của người khác
            if (preg_match('/^sub_id[1-6]$/i', $key)) {
                // Giữ lại nếu người dùng muốn, nhưng mặc định loại bỏ
                // unset($queryParams[$key]);
            }
            
            // Loại bỏ các params có pattern tracking
            if (in_array(strtolower($key), array_map('strtolower', $paramsToRemove))) {
                unset($queryParams[$key]);
            }
        }

        // Rebuild URL
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : 'https://';
        $host = $parsedUrl['host'];
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $query = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

        return $scheme . $host . $port . $path . $query . $fragment;
    }

    /**
     * Kiểm tra URL có phải là homepage không
     */
    public static function isHomepageUrl($url)
    {
        if (empty($url)) return false;
        
        $parsedUrl = parse_url($url);
        $path = isset($parsedUrl['path']) ? trim($parsedUrl['path'], '/') : '';
        
        // Homepage nếu path rỗng hoặc chỉ có locale
        if (empty($path)) return true;
        if (preg_match('/^(vn|sg|th|my|id|ph)$/i', $path)) return true;
        
        return false;
    }

    /**
     * Resolve short URL (s.lazada.vn, c.lazada.vn, etc.)
     */
    public static function resolveShortUrl($url)
    {
        // Kiểm tra có phải short URL hoặc tracking URL không
        if (!preg_match('/^https?:\/\/(s|c)\.lazada\.(vn|sg|co\.th|com\.my|co\.id|com\.ph)\//', $url)) {
            return [
                'success' => true,
                'original_url' => $url,
                'resolved_url' => $url,
            ];
        }
        
        $firstResult = self::followUrl($url);
        
        if (!$firstResult) {
            return [
                'success' => false,
                'original_url' => $url,
                'error' => 'Could not fetch short URL',
            ];
        }
        
        $finalUrl = $firstResult['finalUrl'];
        
        // Thử tìm redirect trong JavaScript
        if (preg_match('/window\.location\.href\s*=\s*["\'](.*?)["\']/i', $firstResult['body'], $matches)) {
            $nextUrl = urldecode($matches[1]);
            if (filter_var($nextUrl, FILTER_VALIDATE_URL)) {
                $secondResult = self::followUrl($nextUrl);
                if ($secondResult) {
                    $finalUrl = $secondResult['finalUrl'];
                }
            }
        }
        
        // Thử tìm meta refresh
        if (strpos($finalUrl, 'lazada') !== false && !preg_match('/-i\d+/', $finalUrl)) {
            if (preg_match('/<meta[^>]*?url=(.*?)["\'\s>]/i', $firstResult['body'], $matches)) {
                $nextUrl = urldecode($matches[1]);
                if (filter_var($nextUrl, FILTER_VALIDATE_URL)) {
                    $secondResult = self::followUrl($nextUrl);
                    if ($secondResult) {
                        $finalUrl = $secondResult['finalUrl'];
                    }
                }
            }
        }
        
        // Thử tìm trong header Location
        if (strpos($finalUrl, 'lazada') !== false && !preg_match('/-i\d+/', $finalUrl)) {
            if (preg_match('/Location:\s*(.*?)[\r\n]/i', $firstResult['headers'], $matches)) {
                $nextUrl = urldecode(trim($matches[1]));
                if (filter_var($nextUrl, FILTER_VALIDATE_URL)) {
                    $secondResult = self::followUrl($nextUrl);
                    if ($secondResult) {
                        $finalUrl = $secondResult['finalUrl'];
                    }
                }
            }
        }
        
        // Xử lý c.lazada redirect
        if (strpos($finalUrl, 'c.lazada.') !== false) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $finalUrl,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => false,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.5',
                    'Cache-Control: no-cache',
                    'Pragma: no-cache',
                    'Referer: ' . $url
                ]
            ]);
            
            $response = curl_exec($ch);
            $newFinalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);
            
            if (strpos($newFinalUrl, 'c.lazada.') !== false) {
                if (preg_match('/window\.location\.href\s*=\s*["\'](.*?)["\']/i', $response, $matches)) {
                    $realUrl = urldecode($matches[1]);
                    if (filter_var($realUrl, FILTER_VALIDATE_URL)) {
                        $finalResult = self::followUrl($realUrl);
                        if ($finalResult) {
                            $finalUrl = $finalResult['finalUrl'];
                        }
                    }
                }
            } else {
                $finalUrl = $newFinalUrl;
            }
        }
        
        // Kiểm tra nếu resolved URL là homepage -> coi như resolve thất bại
        if (self::isHomepageUrl($finalUrl)) {
            return [
                'success' => false,
                'original_url' => $url,
                'resolved_url' => $finalUrl,
                'error' => 'Resolved to homepage - link may have expired or invalid',
                'is_homepage' => true,
            ];
        }
        
        if ($finalUrl && $finalUrl !== $url) {
            return [
                'success' => true,
                'original_url' => $url,
                'resolved_url' => $finalUrl,
            ];
        }
        
        return [
            'success' => false,
            'original_url' => $url,
            'error' => 'Could not resolve short URL to product page',
        ];
    }

    /**
     * Phân tích input để xác định loại và giá trị
     * Trả về: ['type' => 'productId|url|offerId', 'value' => '...', 'cleaned_url' => '...', ...]
     */
    public static function analyzeInput($input)
    {
        $input = trim($input);
        
        // Nếu là số thuần túy -> Product ID
        if (is_numeric($input)) {
            return [
                'type' => 'productId',
                'value' => $input,
                'original_input' => $input,
            ];
        }

        // Nếu không phải URL hợp lệ
        if (!filter_var($input, FILTER_VALIDATE_URL)) {
            // Có thể là Product ID dạng string hoặc Offer ID
            if (preg_match('/^\d+$/', $input)) {
                return [
                    'type' => 'productId',
                    'value' => $input,
                    'original_input' => $input,
                ];
            }
            
            return [
                'type' => 'invalid',
                'error' => 'Input không hợp lệ. Vui lòng nhập URL Lazada hoặc Product ID.',
                'original_input' => $input,
            ];
        }

        // Là URL - tiến hành phân tích
        $resolvedUrl = $input;
        $wasShortUrl = false;
        $resolveError = null;
        $isHomepageRedirect = false;

        // Resolve short URL hoặc tracking URL nếu cần
        if (preg_match('/^https?:\/\/(s|c)\.lazada\.(vn|sg|co\.th|com\.my|co\.id|com\.ph)\//', $input)) {
            $resolveResult = self::resolveShortUrl($input);
            
            if ($resolveResult['success'] && isset($resolveResult['resolved_url'])) {
                $resolvedUrl = $resolveResult['resolved_url'];
                $wasShortUrl = true;
            } else {
                // Resolve thất bại - có thể là homepage redirect hoặc lỗi khác
                $resolveError = $resolveResult['error'] ?? 'Unknown error';
                $isHomepageRedirect = isset($resolveResult['is_homepage']) && $resolveResult['is_homepage'];
                
                // Nếu resolve về homepage, dùng URL gốc (đã clean) thay vì resolved URL
                if ($isHomepageRedirect) {
                    $resolvedUrl = $input; // Giữ nguyên URL gốc
                    $wasShortUrl = true; // Đánh dấu là short URL nhưng không resolve được
                }
            }
        }

        // Clean tracking params từ resolved URL (hoặc original nếu resolve thất bại)
        $cleanedUrl = self::cleanTrackingParams($resolvedUrl);

        // Kiểm tra có phải Product URL không (chứa -i{digits})
        if (preg_match('/-i(\d+)(?:-s|\.|$|\?)/', $cleanedUrl, $matches)) {
            return [
                'type' => 'productId',
                'value' => $matches[1],
                'original_input' => $input,
                'resolved_url' => $resolvedUrl,
                'cleaned_url' => $cleanedUrl,
                'was_short_url' => $wasShortUrl,
                'is_product_url' => true,
                'resolve_error' => $resolveError,
                'is_homepage_redirect' => $isHomepageRedirect,
            ];
        }

        // Các pattern khác cho Product URL
        $productPatterns = [
            '/\/i(\d+)(?:-|\.|$|\?)/',
            '/itemId=(\d+)/',
            '/product\/(\d+)/',
        ];

        foreach ($productPatterns as $pattern) {
            if (preg_match($pattern, $cleanedUrl, $matches)) {
                return [
                    'type' => 'productId',
                    'value' => $matches[1],
                    'original_input' => $input,
                    'resolved_url' => $resolvedUrl,
                    'cleaned_url' => $cleanedUrl,
                    'was_short_url' => $wasShortUrl,
                    'is_product_url' => true,
                    'resolve_error' => $resolveError,
                    'is_homepage_redirect' => $isHomepageRedirect,
                ];
            }
        }

        // Không phải Product URL -> sử dụng inputType = url
        // Các loại URL này bao gồm: campaign pages, category pages, brand pages, tracking links, etc.
        return [
            'type' => 'url',
            'value' => $cleanedUrl,
            'original_input' => $input,
            'resolved_url' => $resolvedUrl,
            'cleaned_url' => $cleanedUrl,
            'was_short_url' => $wasShortUrl,
            'is_product_url' => false,
            'resolve_error' => $resolveError,
            'is_homepage_redirect' => $isHomepageRedirect,
        ];
    }

    /**
     * Tạo tracking link thông minh - tự động detect loại input
     */
    public function getTrackingLink($input, $mmCampaignId = null, $dmInviteId = null, $subAffId = null, $subIds = [])
    {
        $analysis = self::analyzeInput($input);

        if ($analysis['type'] === 'invalid') {
            return [
                'success' => false,
                'error' => $analysis['error'],
                'analysis' => $analysis,
            ];
        }

        $result = null;
        
        switch ($analysis['type']) {
            case 'productId':
                $result = $this->getTrackingLinkByProductId(
                    $analysis['value'],
                    $mmCampaignId,
                    $dmInviteId,
                    $subAffId,
                    $subIds
                );
                break;
                
            case 'url':
                $result = $this->getTrackingLinkByUrl(
                    $analysis['value'],
                    $mmCampaignId,
                    $dmInviteId,
                    $subAffId,
                    $subIds
                );
                break;
                
            case 'offerId':
                $result = $this->getTrackingLinkByOfferId(
                    $analysis['value'],
                    $mmCampaignId,
                    $dmInviteId,
                    $subAffId,
                    $subIds
                );
                break;
        }

        if ($result) {
            $result['analysis'] = $analysis;
        }

        return $result;
    }

    public static function formatCommissionRate($rate)
    {
        if ($rate === '' || $rate === null) {
            return null;
        }
        
        $numericRate = floatval($rate);
        
        if ($numericRate > 0 && $numericRate < 1) {
            $percentage = $numericRate * 100;
        } else {
            $percentage = $numericRate;
        }
        
        return number_format($percentage, 2) . '%';
    }
}

// ==============================================================================
// XỬ LÝ AJAX REQUEST
// ==============================================================================

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'error' => 'Invalid action'];
    
    $config = $_SESSION['config'] ?? null;
    
    if (!$config || empty($config['app_key']) || empty($config['app_secret']) || empty($config['user_token'])) {
        echo json_encode(['success' => false, 'error' => 'Vui lòng cấu hình API trước!']);
        exit;
    }
    
    $lazada = new LazadaAffiliateAPI($config);
    
    switch ($action) {
        case 'get_tracking_link':
            $productInput = trim($_POST['product_input'] ?? '');
            $mmCampaignId = $_POST['mm_campaign_id'] ?? null;
            $dmInviteId = $_POST['dm_invite_id'] ?? null;
            $subAffId = $_POST['sub_aff_id'] ?? null;
            
            // Thu thập sub IDs
            $subIds = [];
            for ($i = 1; $i <= 6; $i++) {
                if (isset($_POST["sub_id$i"]) && $_POST["sub_id$i"] !== '') {
                    $subIds["subId$i"] = $_POST["sub_id$i"];
                }
            }
            
            if (!$productInput) {
                $response = ['success' => false, 'error' => 'Vui lòng nhập URL hoặc Product ID!'];
            } else {
                // Sử dụng method thông minh - tự động detect loại input
                $result = $lazada->getTrackingLink($productInput, $mmCampaignId, $dmInviteId, $subAffId, $subIds);
                $response = $result;
            }
            break;
            
        case 'analyze_input':
            // Debug: phân tích input mà không gọi API
            $input = trim($_POST['input'] ?? '');
            if ($input) {
                $analysis = LazadaAffiliateAPI::analyzeInput($input);
                $response = [
                    'success' => true,
                    'analysis' => $analysis,
                ];
            } else {
                $response = ['success' => false, 'error' => 'Input trống'];
            }
            break;
            
        case 'get_conversion_page':
            $dateStart = $_POST['date_start'] ?? date('Y-m-d', strtotime('-7 days'));
            $dateEnd = $_POST['date_end'] ?? date('Y-m-d');
            $page = $_POST['page'] ?? 1;
            $offerId = $_POST['offer_id'] ?? null;
            $mmPartnerFlag = isset($_POST['mm_partner_flag']) && $_POST['mm_partner_flag'] == '1';
            
            $result = $lazada->getConversionReport($dateStart, $dateEnd, 100, $page, $offerId, $mmPartnerFlag);
            
            if ($result['success']) {
                $data = $result['data'];
                $conversions = [];
                
                if (isset($data['result']['data']) && is_array($data['result']['data'])) {
                    $conversions = $data['result']['data'];
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    $conversions = $data['data'];
                } elseif (is_array($data)) {
                    $conversions = $data;
                }
                
                $response = [
                    'success' => true,
                    'conversions' => $conversions,
                    'count' => count($conversions),
                    'page' => (int)$page,
                    'has_more' => count($conversions) === 100,
                ];
            } else {
                $response = $result;
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}

// ==============================================================================
// LƯU CONFIG
// ==============================================================================

$message = '';
$messageType = '';

if (isset($_POST['save_config'])) {
    $_SESSION['config'] = [
        'app_key' => trim($_POST['app_key'] ?? ''),
        'app_secret' => trim($_POST['app_secret'] ?? ''),
        'user_token' => trim($_POST['user_token'] ?? ''),
        'base_url' => $_POST['base_url'] ?? 'https://api.lazada.vn',
    ];
    $message = 'Đã lưu cấu hình thành công!';
    $messageType = 'success';
}

$config = $_SESSION['config'] ?? [
    'app_key' => '',
    'app_secret' => '',
    'user_token' => '',
    'base_url' => 'https://api.lazada.vn',
];

$configComplete = !empty($config['app_key']) && !empty($config['app_secret']) && !empty($config['user_token']);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lazada Affiliate API Tool v1.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --lazada-primary: #0f146d;
            --lazada-secondary: #f85606;
            --lazada-light: #f5f5f5;
        }
        
        body {
            background-color: #f8f9fa;
            padding-bottom: 50px;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--lazada-primary) 0%, #1a237e 100%);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--lazada-primary) 0%, #1a237e 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
        }
        
        .btn-lazada {
            background: linear-gradient(135deg, var(--lazada-secondary) 0%, #ff6f00 100%);
            border: none;
            color: white;
            font-weight: 600;
        }
        
        .btn-lazada:hover {
            background: linear-gradient(135deg, #e64a19 0%, #e65100 100%);
            color: white;
        }
        
        .btn-lazada:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .nav-tabs .nav-link {
            color: var(--lazada-primary);
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--lazada-secondary);
            border-bottom: 3px solid var(--lazada-secondary);
        }
        
        .tracking-link-box {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #4caf50;
        }
        
        .tracking-link-box .link-text {
            word-break: break-all;
            font-size: 0.95rem;
            color: #1b5e20;
        }
        
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .config-badge {
            font-size: 0.75rem;
            padding: 10px;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
        }
        
        .input-group-text {
            background: var(--lazada-light);
        }
        
        pre {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            max-height: 300px;
            overflow: auto;
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--lazada-primary) 0%, #303f9f 100%);
            color: white;
            border-radius: 12px;
            padding: 10px;
            text-align: center;
        }
        
        .stats-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .stats-card.green {
            background: linear-gradient(135deg, #388e3c 0%, #4caf50 100%);
        }
        
        .stats-card.orange {
            background: linear-gradient(135deg, #f57c00 0%, #ff9800 100%);
        }
        
        .stats-card.red {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        .product-info-card {
            background: #fff3e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .extracted-id {
            background: #e3f2fd;
            border-radius: 6px;
            padding: 8px 12px;
            display: inline-block;
            margin-top: 8px;
        }
        
        .resolved-url-info {
            background: #fff8e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 8px;
            border-left: 3px solid #ff9800;
        }
        
        .original-input-info {
            background: #f5f5f5;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 8px;
            border-left: 3px solid #9e9e9e;
        }
        
        .input-type-badge {
            background: #e1f5fe;
            border-radius: 6px;
            padding: 8px 12px;
            display: inline-block;
            margin-top: 8px;
            border-left: 3px solid #03a9f4;
        }
        
        .input-type-badge.url-type {
            background: #f3e5f5;
            border-left-color: #9c27b0;
        }
        
        .input-type-badge.product-type {
            background: #e8f5e9;
            border-left-color: #4caf50;
        }
        
        .cleaned-url-info {
            background: #e8f5e9;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 8px;
            border-left: 3px solid #4caf50;
        }
        
        .excluded-row {
            background-color: #fff5f5 !important;
            opacity: 0.7;
        }
        
        .excluded-row td {
            text-decoration: line-through;
            color: #999;
        }
        
        .excluded-row td .badge {
            text-decoration: none;
        }
        
        /* Loading Overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
        }
        
        .loading-overlay.show {
            display: flex;
        }
        
        .loading-box {
            background: white;
            padding: 40px 60px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            animation: fadeInUp 0.3s ease;
            min-width: 350px;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--lazada-secondary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: var(--lazada-primary);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .loading-subtext {
            color: #666;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .loading-progress {
            margin-top: 15px;
        }
        
        .loading-progress .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .loading-progress .progress-bar {
            background: linear-gradient(135deg, var(--lazada-secondary) 0%, #ff6f00 100%);
            transition: width 0.3s ease;
        }
        
        .loading-stats {
            margin-top: 10px;
            font-size: 0.85rem;
            color: #888;
        }
        
        /* Result animations */
        .result-container {
            animation: fadeIn 0.4s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Button loading state */
        .btn-loading {
            position: relative;
            pointer-events: none;
        }
        
        .btn-loading .btn-text {
            visibility: hidden;
        }
        
        .btn-loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        /* Toast notification */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        }
        
        .custom-toast {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
            margin-bottom: 10px;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .custom-toast.success {
            border-left: 4px solid #4caf50;
        }
        
        .custom-toast.error {
            border-left: 4px solid #dc3545;
        }
        
        .custom-toast .toast-icon {
            font-size: 1.5rem;
        }
        
        .custom-toast.success .toast-icon {
            color: #4caf50;
        }
        
        .custom-toast.error .toast-icon {
            color: #dc3545;
        }
        
        /* Search box */
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding-left: 40px;
            border-radius: 25px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            border-color: var(--lazada-secondary);
            box-shadow: 0 0 0 3px rgba(248, 86, 6, 0.1);
        }
        
        .search-box .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .search-box .clear-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
            display: none;
        }
        
        .search-box .clear-search.show {
            display: block;
        }
        
        .search-box .clear-search:hover {
            color: var(--lazada-secondary);
        }
        
        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .pagination-info {
            color: #666;
            font-size: 0.9rem;
        }
        
        .pagination-controls {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .pagination-controls .btn {
            min-width: 40px;
        }
        
        .pagination-controls .page-number {
            padding: 6px 12px;
            background: var(--lazada-primary);
            color: white;
            border-radius: 6px;
            font-weight: 600;
        }
        
        /* Highlight search */
        .highlight {
            background-color: #fff59d;
            padding: 1px 3px;
            border-radius: 3px;
        }
        
        /* No results */
        .no-results {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .no-results i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }
        
        /* Table sticky header */
        .table thead th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 10;
        }
        
        /* Per page selector */
        .per-page-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .per-page-selector select {
            width: auto;
            display: inline-block;
        }
        
        /* Sub IDs toggle */
        .sub-ids-toggle {
            cursor: pointer;
            color: var(--lazada-primary);
            font-size: 0.9rem;
        }
        
        .sub-ids-toggle:hover {
            color: var(--lazada-secondary);
        }
        
        .sub-ids-container {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .sub-ids-container.show {
            display: block;
        }
        
        /* Daily Chart Container */
        .daily-chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .daily-chart-container h6 {
            color: var(--lazada-primary);
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-box">
            <div class="loading-spinner"></div>
            <div class="loading-text" id="loadingText">Đang xử lý...</div>
            <div class="loading-subtext" id="loadingSubtext">Vui lòng chờ trong giây lát</div>
            <div class="loading-progress" id="loadingProgress" style="display: none;">
                <div class="progress">
                    <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                </div>
                <div class="loading-stats" id="loadingStats"></div>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="bi bi-shop me-2"></i>
                <span>Lazada Affiliate API Tool <small class="badge bg-warning text-dark ms-2">v1.0</small></span>
            </a>
            <div class="d-flex align-items-center">
                <?php if ($configComplete): ?>
                    <span class="badge bg-success config-badge me-2">
                        <i class="bi bi-check-circle me-1"></i>Đã cấu hình
                    </span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark config-badge me-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>Chưa cấu hình
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Sidebar Config -->
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-gear me-2"></i>Cấu hình API
                    </div>
                    <div class="card-body">
                        <form method="POST" id="configForm">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-key me-1"></i>LiteApp Key <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" name="app_key" 
                                       value="<?= htmlspecialchars($config['app_key']) ?>" 
                                       placeholder="Nhập LiteApp Key" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-shield-lock me-1"></i>LiteApp Secret <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" name="app_secret" 
                                       value="<?= htmlspecialchars($config['app_secret']) ?>" 
                                       placeholder="Nhập LiteApp Secret" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-person-badge me-1"></i>User Token <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" name="user_token" 
                                       value="<?= htmlspecialchars($config['user_token']) ?>" 
                                       placeholder="Nhập User Token" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-globe me-1"></i>Quốc gia
                                </label>
                                <select class="form-select" name="base_url">
                                    <option value="https://api.lazada.vn" <?= $config['base_url'] === 'https://api.lazada.vn' ? 'selected' : '' ?>>🇻🇳 Vietnam</option>
                                    <option value="https://api.lazada.sg" <?= $config['base_url'] === 'https://api.lazada.sg' ? 'selected' : '' ?>>🇸🇬 Singapore</option>
                                    <option value="https://api.lazada.co.th" <?= $config['base_url'] === 'https://api.lazada.co.th' ? 'selected' : '' ?>>🇹🇭 Thailand</option>
                                    <option value="https://api.lazada.com.my" <?= $config['base_url'] === 'https://api.lazada.com.my' ? 'selected' : '' ?>>🇲🇾 Malaysia</option>
                                    <option value="https://api.lazada.co.id" <?= $config['base_url'] === 'https://api.lazada.co.id' ? 'selected' : '' ?>>🇮🇩 Indonesia</option>
                                    <option value="https://api.lazada.com.ph" <?= $config['base_url'] === 'https://api.lazada.com.ph' ? 'selected' : '' ?>>🇵🇭 Philippines</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="save_config" class="btn btn-lazada w-100">
                                <i class="bi bi-save me-2"></i>Lưu cấu hình
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Quick Help -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-question-circle me-2"></i>Hướng dẫn
                    </div>
                    <div class="card-body">
                        <div class="accordion accordion-flush" id="helpAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#help1">
                                        Lấy thông tin API ở đâu?
                                    </button>
                                </h2>
                                <div id="help1" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body small">
                                        <ol class="mb-0">
                                            <li>Đăng nhập <a href="https://adsense.lazada.vn" target="_blank">Lazada Affiliate</a></li>
                                            <li>Vào mục <strong>Tích hợp > Mở API</strong></li>
                                            <li>Copy các thông tin: App Key, Secret, Token</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#help2">
                                        Loại URL được hỗ trợ?
                                    </button>
                                </h2>
                                <div id="help2" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body small">
                                        <p class="mb-2"><strong>Hỗ trợ tất cả loại URL:</strong></p>
                                        <ul class="mb-0">
                                            <li>URL sản phẩm (có -i{id})</li>
                                            <li>URL chiến dịch (pages.lazada)</li>
                                            <li>URL danh mục</li>
                                            <li>Short link (<code>s.lazada.vn</code>)</li>
                                            <li>Tracking link (<code>c.lazada.vn</code>)</li>
                                            <li>Product ID trực tiếp</li>
                                        </ul>
                                        <hr>
                                        <p class="mb-0 text-success">
                                            <i class="bi bi-check-circle me-1"></i>
                                            <small>Tự động loại bỏ tracking params của người khác!</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-4" id="mainTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tracking-tab" data-bs-toggle="tab" 
                                        data-bs-target="#tracking" type="button">
                                    <i class="bi bi-link-45deg me-1"></i>Tạo Tracking Link
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="conversion-tab" data-bs-toggle="tab" 
                                        data-bs-target="#conversion" type="button">
                                    <i class="bi bi-graph-up me-1"></i>Báo cáo chuyển đổi
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="mainTabContent">
                            <!-- Tab: Tracking Link -->
                            <div class="tab-pane fade show active" id="tracking" role="tabpanel">
                                <form id="trackingForm">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-link me-1"></i>URL hoặc Product ID <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-box"></i></span>
                                            <input type="text" class="form-control" name="product_input" id="productInput"
                                                   placeholder="Paste bất kỳ URL Lazada nào (sản phẩm, chiến dịch, danh mục...) hoặc Product ID" required>
                                        </div>
                                        <div class="form-text text-muted">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Hỗ trợ: URL sản phẩm, URL chiến dịch, Short link, Tracking link (tự động clean), Product ID
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">MM Campaign ID <span class="text-muted">(tùy chọn)</span></label>
                                            <input type="text" class="form-control" name="mm_campaign_id" id="mmCampaignId"
                                                   placeholder="Cho MM Offer">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">DM Invite ID <span class="text-muted">(tùy chọn)</span></label>
                                            <input type="text" class="form-control" name="dm_invite_id" id="dmInviteId"
                                                   placeholder="Cho DM Offer">
                                        </div>
                                    </div>
                                    
                                    <!-- Sub IDs (collapsible) -->
                                    <div class="mb-3">
                                        <span class="sub-ids-toggle" onclick="toggleSubIds()">
                                            <i class="bi bi-chevron-right me-1" id="subIdsChevron"></i>
                                            Thêm Sub IDs (tùy chọn)
                                        </span>
                                    </div>
                                    
                                    <div class="sub-ids-container" id="subIdsContainer">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Sub Aff ID</label>
                                                <input type="text" class="form-control form-control-sm" name="sub_aff_id" id="subAffId"
                                                       placeholder="subAffId">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Sub ID 1</label>
                                                <input type="text" class="form-control form-control-sm" name="sub_id1" id="subId1"
                                                       placeholder="subId1">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Sub ID 2</label>
                                                <input type="text" class="form-control form-control-sm" name="sub_id2" id="subId2"
                                                       placeholder="subId2">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Sub ID 3</label>
                                                <input type="text" class="form-control form-control-sm" name="sub_id3" id="subId3"
                                                       placeholder="subId3">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Sub ID 4</label>
                                                <input type="text" class="form-control form-control-sm" name="sub_id4" id="subId4"
                                                       placeholder="subId4">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Sub ID 5</label>
                                                <input type="text" class="form-control form-control-sm" name="sub_id5" id="subId5"
                                                       placeholder="subId5">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Sub ID 6</label>
                                                <input type="text" class="form-control form-control-sm" name="sub_id6" id="subId6"
                                                       placeholder="subId6">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-lazada" id="btnTracking" <?= !$configComplete ? 'disabled' : '' ?>>
                                        <span class="btn-text"><i class="bi bi-lightning me-2"></i>Tạo Tracking Link</span>
                                    </button>
                                    
                                    <?php if (!$configComplete): ?>
                                        <small class="text-danger ms-2"><i class="bi bi-exclamation-circle"></i> Cần cấu hình API trước</small>
                                    <?php endif; ?>
                                </form>

                                <div id="trackingResult" class="mt-4"></div>
                            </div>

                            <!-- Tab: Conversion Report -->
                            <div class="tab-pane fade" id="conversion" role="tabpanel">
                                <form id="conversionForm">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Từ ngày <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="date_start" id="dateStart"
                                                   value="<?= date('Y-m-d', strtotime('-7 days')) ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Đến ngày <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="date_end" id="dateEnd"
                                                   value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Số kết quả/trang hiển thị</label>
                                            <select class="form-select" name="per_page" id="perPage">
                                                <option value="20">20</option>
                                                <option value="50" selected>50</option>
                                                <option value="100">100</option>
                                                <option value="200">200</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Offer ID <span class="text-muted">(tùy chọn)</span></label>
                                            <input type="text" class="form-control" name="offer_id" id="offerId"
                                                   placeholder="Lọc theo Offer">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">&nbsp;</label>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="mm_partner_flag" 
                                                       value="1" id="mmPartnerFlag">
                                                <label class="form-check-label" for="mmPartnerFlag">
                                                    MM Partner Mode
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-lazada" id="btnConversion" <?= !$configComplete ? 'disabled' : '' ?>>
                                        <span class="btn-text"><i class="bi bi-search me-2"></i>Lấy báo cáo</span>
                                    </button>
                                    
                                    <?php if (!$configComplete): ?>
                                        <small class="text-danger ms-2"><i class="bi bi-exclamation-circle"></i> Cần cấu hình API trước</small>
                                    <?php endif; ?>
                                </form>

                                <div id="conversionResult" class="mt-4"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ==============================================================================
        // GLOBAL STATE
        // ==============================================================================
        
        let conversionState = {
            allData: [],
            filteredData: [],
            currentPage: 1,
            perPage: 50,
            totalPages: 1,
            searchTerm: '',
            dateStart: '',
            dateEnd: '',
        };
        
        let dailyChart = null;
        
        // ==============================================================================
        // UTILITY FUNCTIONS
        // ==============================================================================
        
        function toggleSubIds() {
            const container = document.getElementById('subIdsContainer');
            const chevron = document.getElementById('subIdsChevron');
            container.classList.toggle('show');
            chevron.classList.toggle('bi-chevron-right');
            chevron.classList.toggle('bi-chevron-down');
        }
        
        function showLoading(text = 'Đang xử lý...', subtext = 'Vui lòng chờ trong giây lát', showProgress = false) {
            document.getElementById('loadingText').textContent = text;
            document.getElementById('loadingSubtext').textContent = subtext;
            document.getElementById('loadingProgress').style.display = showProgress ? 'block' : 'none';
            document.getElementById('loadingOverlay').classList.add('show');
        }
        
        function updateLoadingProgress(loaded, text = '') {
            document.getElementById('progressBar').style.width = '100%';
            document.getElementById('loadingStats').textContent = text || `Đã tải ${loaded} đơn hàng...`;
        }
        
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('show');
        }
        
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `custom-toast ${type}`;
            toast.innerHTML = `
                <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'x-circle-fill'} toast-icon"></i>
                <span>${message}</span>
            `;
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        function formatNumber(num) {
            return new Intl.NumberFormat('vi-VN').format(num);
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Đã copy link thành công!', 'success');
            });
        }
        
        function formatCommissionRate(rate) {
            if (rate === '' || rate === null || rate === undefined) {
                return 'N/A';
            }
            
            const numericRate = parseFloat(rate);
            if (isNaN(numericRate)) {
                return rate;
            }
            
            let percentage;
            if (numericRate > 0 && numericRate < 1) {
                percentage = numericRate * 100;
            } else {
                percentage = numericRate;
            }
            
            return percentage.toFixed(2) + '%';
        }
        
        function isExcludedOrder(conversion) {
            const status = (conversion.status || '').toLowerCase();
            const validity = (conversion.validity || '').toLowerCase();
            
            if (status.includes('reject') || validity.includes('reject')) return 'rejected';
            if (status.includes('return') || validity.includes('return')) return 'returned';
            if (status.includes('cancel') || validity.includes('cancel')) return 'cancelled';
            
            return false;
        }
        
        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDateTime(dateString) {
            if (!dateString || dateString === 'N/A') return 'N/A';
            
            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return dateString;
                
                const hours = date.getHours().toString().padStart(2, '0');
                const minutes = date.getMinutes().toString().padStart(2, '0');
                const day = date.getDate().toString().padStart(2, '0');
                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                const year = date.getFullYear();
                
                return `${hours}:${minutes} ${day}/${month}/${year}`;
            } catch (e) {
                return dateString;
            }
        }
        
        function formatDateShort(dateString) {
            // Convert YYYY-MM-DD to DD/MM
            if (!dateString) return '';
            const parts = dateString.split('-');
            if (parts.length === 3) {
                return `${parts[2]}/${parts[1]}`;
            }
            return dateString;
        }
        
        function highlightText(text, searchTerm) {
            if (!searchTerm || !text) return escapeHtml(text);
            const escaped = escapeHtml(text);
            const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            return escaped.replace(regex, '<span class="highlight">$1</span>');
        }
        
        function getDateRange(startDate, endDate) {
            const dates = [];
            const currentDate = new Date(startDate);
            const end = new Date(endDate);
            
            while (currentDate <= end) {
                dates.push(currentDate.toISOString().split('T')[0]);
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            return dates;
        }
        
        function aggregateDataByDate(conversions, dateStart, dateEnd) {
            // Get all dates in range
            const allDates = getDateRange(dateStart, dateEnd);
            
            // Initialize data structure
            const dailyData = {};
            allDates.forEach(date => {
                dailyData[date] = {
                    orderCount: 0,
                    payout: 0,
                    orderAmt: 0,
                    excludedCount: 0
                };
            });
            
            // Aggregate conversions
            conversions.forEach(c => {
                // Get date from conversionTime or fulfilledTime
                const timeStr = c.conversionTime || c.fulfilledTime || '';
                if (!timeStr) return;
                
                let dateKey;
                try {
                    const date = new Date(timeStr);
                    if (isNaN(date.getTime())) return;
                    // Use local timezone instead of UTC
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    dateKey = `${year}-${month}-${day}`;
                } catch (e) {
                    return;
                }
                
                if (dailyData[dateKey]) {
                    const excludeType = isExcludedOrder(c);
                    if (excludeType) {
                        dailyData[dateKey].excludedCount++;
                    } else {
                        dailyData[dateKey].orderCount++;
                        dailyData[dateKey].payout += parseFloat(c.estPayout || 0);
                        dailyData[dateKey].orderAmt += parseFloat(c.orderAmt || 0);
                    }
                }
            });
            
            return {
                dates: allDates,
                labels: allDates.map(formatDateShort),
                orderCounts: allDates.map(d => dailyData[d].orderCount),
                payouts: allDates.map(d => Math.round(dailyData[d].payout)),
                orderAmts: allDates.map(d => Math.round(dailyData[d].orderAmt)),
                excludedCounts: allDates.map(d => dailyData[d].excludedCount)
            };
        }
        
        function renderDailyChart(conversions, dateStart, dateEnd) {
            const chartData = aggregateDataByDate(conversions, dateStart, dateEnd);
            
            // Destroy existing chart if any
            if (dailyChart) {
                dailyChart.destroy();
            }
            
            const ctx = document.getElementById('dailyChart');
            if (!ctx) return;
            
            dailyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: 'Hoa hồng (₫)',
                            data: chartData.payouts,
                            backgroundColor: 'rgba(75, 192, 192, 0.8)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                            yAxisID: 'y1',
                            order: 2
                        },
                        {
                            label: 'Số đơn',
                            data: chartData.orderCounts,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 2,
                            type: 'line',
                            yAxisID: 'y',
                            order: 1,
                            tension: 0.3,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                title: function(context) {
                                    const index = context[0].dataIndex;
                                    const dateStr = chartData.dates[index];
                                    // Format to dd/mm/yyyy
                                    const parts = dateStr.split('-');
                                    return `${parts[2]}/${parts[1]}/${parts[0]}`;
                                },
                                label: function(context) {
                                    return null; // We'll use afterBody instead
                                },
                                afterBody: function(context) {
                                    const index = context[0].dataIndex;
                                    const orderCount = chartData.orderCounts[index];
                                    const payout = chartData.payouts[index];
                                    const orderAmt = chartData.orderAmts[index];
                                    const excluded = chartData.excludedCounts[index];
                                    
                                    const lines = [
                                        `Đơn hợp lệ: ${orderCount}`,
                                        `Hoa hồng ước tính: ${formatNumber(payout)}đ`,
                                        `Doanh số: ${formatNumber(orderAmt)}đ`
                                    ];
                                    
                                    if (excluded > 0) {
                                        lines.push(`Hủy/Hoàn: ${excluded}`);
                                    }
                                    
                                    return lines;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    if (Number.isInteger(value)) {
                                        return value;
                                    }
                                }
                            },
                            title: {
                                display: true,
                                text: 'Số đơn'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN', { 
                                        notation: 'compact',
                                        compactDisplay: 'short'
                                    }).format(value);
                                }
                            },
                            title: {
                                display: true,
                                text: 'Hoa hồng (₫)'
                            }
                        }
                    }
                }
            });
        }
        
        // ==============================================================================
        // TRACKING LINK HANDLER
        // ==============================================================================
        
        document.getElementById('trackingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('btnTracking');
            btn.classList.add('btn-loading');
            showLoading('Đang tạo Tracking Link...', 'Đang phân tích URL và kết nối API');
            
            const formData = new FormData();
            formData.append('action', 'get_tracking_link');
            formData.append('product_input', document.getElementById('productInput').value);
            formData.append('mm_campaign_id', document.getElementById('mmCampaignId').value);
            formData.append('dm_invite_id', document.getElementById('dmInviteId').value);
            formData.append('sub_aff_id', document.getElementById('subAffId').value);
            
            // Sub IDs
            for (let i = 1; i <= 6; i++) {
                const subIdEl = document.getElementById(`subId${i}`);
                if (subIdEl && subIdEl.value) {
                    formData.append(`sub_id${i}`, subIdEl.value);
                }
            }
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                
                const result = await response.json();
                hideLoading();
                btn.classList.remove('btn-loading');
                
                renderTrackingResult(result);
                
            } catch (error) {
                hideLoading();
                btn.classList.remove('btn-loading');
                showToast('Có lỗi xảy ra: ' + error.message, 'error');
            }
        });
        
        function renderTrackingResult(result) {
            const container = document.getElementById('trackingResult');
            const analysis = result.analysis || {};
            
            if (!result.success) {
                let html = '<div class="result-container">';
                html += `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Lỗi:</strong> ${escapeHtml(result.error || 'Unknown error')}
                        ${result.error_code ? `<br><small>Error Code: ${escapeHtml(result.error_code)}</small>` : ''}
                    </div>
                `;
                
                // Hiển thị thông tin phân tích nếu có
                if (analysis.type) {
                    html += renderAnalysisInfo(analysis);
                }
                
                html += '</div>';
                container.innerHTML = html;
                showToast('Không thể tạo tracking link!', 'error');
                return;
            }
            
            const data = result.data;
            let trackingLink = '';
            let productName = '';
            let commissionRate = '';
            
            // Parse response data theo nhiều cấu trúc có thể có
            if (data.result && data.result.data) {
                const resultData = data.result.data;
                
                // Kiểm tra nếu là response theo inputType = url
                if (resultData.urlBatchGetLinkInfoList && resultData.urlBatchGetLinkInfoList.length > 0) {
                    const urlInfo = resultData.urlBatchGetLinkInfoList[0];
                    // Thứ tự ưu tiên: regular > offer > mm > dm
                    trackingLink = urlInfo.regularPromotionLink || urlInfo.offerPromotionLink || urlInfo.mmPromotionLink || urlInfo.dmPromotionLink || '';
                    // Ưu tiên skuName > productName > originalUrl
                    productName = urlInfo.skuName || urlInfo.productName || '';
                    commissionRate = urlInfo.regularCommission || urlInfo.offerCommission || urlInfo.mmCommission || urlInfo.dmCommission || '';
                }
                // Response theo inputType = productId
                else if (resultData.productBatchGetLinkInfoList && resultData.productBatchGetLinkInfoList.length > 0) {
                    const productInfo = resultData.productBatchGetLinkInfoList[0];
                    trackingLink = productInfo.regularPromotionLink || productInfo.offerPromotionLink || productInfo.mmPromotionLink || productInfo.dmPromotionLink || '';
                    // Ưu tiên skuName > productName
                    productName = productInfo.skuName || productInfo.productName || '';
                    commissionRate = productInfo.regularCommission || productInfo.offerCommission || productInfo.mmCommission || productInfo.dmCommission || '';
                }
                // Response theo inputType = offerId
                else if (resultData.offerBatchGetLinkInfoList && resultData.offerBatchGetLinkInfoList.length > 0) {
                    const offerInfo = resultData.offerBatchGetLinkInfoList[0];
                    trackingLink = offerInfo.offerPromotionLink || offerInfo.regularPromotionLink || '';
                    productName = offerInfo.skuName || offerInfo.offerName || offerInfo.productName || '';
                    commissionRate = offerInfo.offerCommission || offerInfo.regularCommission || '';
                }
                // Fallback cho cấu trúc cũ
                else {
                    trackingLink = resultData.trackingLink || resultData.regularPromotionLink || resultData.offerPromotionLink || '';
                    productName = resultData.skuName || resultData.productName || '';
                    commissionRate = resultData.commisionRate || resultData.commissionRate || resultData.regularCommission || '';
                }
            } else if (data.data) {
                if (Array.isArray(data.data) && data.data.length > 0) {
                    const firstItem = data.data[0];
                    trackingLink = firstItem.regularPromotionLink || firstItem.offerPromotionLink || firstItem.trackingLink || '';
                    productName = firstItem.skuName || firstItem.productName || '';
                    commissionRate = firstItem.regularCommission || firstItem.offerCommission || firstItem.commisionRate || '';
                } else {
                    trackingLink = data.data.trackingLink || data.data.regularPromotionLink || data.data.offerPromotionLink || '';
                    productName = data.data.skuName || data.data.productName || '';
                    commissionRate = data.data.commisionRate || data.data.regularCommission || data.data.offerCommission || '';
                }
            }
            
            let html = '<div class="result-container"><hr class="my-4">';
            
            // Hiển thị thông tin phân tích input
            html += renderAnalysisInfo(analysis);
            
            if (trackingLink) {
                // Detect link type
                let linkType = 'Regular';
                let linkTypeClass = 'primary';
                if (data.result && data.result.data) {
                    const resultData = data.result.data;
                    if (resultData.urlBatchGetLinkInfoList && resultData.urlBatchGetLinkInfoList.length > 0) {
                        const info = resultData.urlBatchGetLinkInfoList[0];
                        if (info.offerPromotionLink && trackingLink === info.offerPromotionLink) {
                            linkType = 'Offer';
                            linkTypeClass = 'warning';
                        } else if (info.mmPromotionLink && trackingLink === info.mmPromotionLink) {
                            linkType = 'MM';
                            linkTypeClass = 'info';
                        } else if (info.dmPromotionLink && trackingLink === info.dmPromotionLink) {
                            linkType = 'DM';
                            linkTypeClass = 'secondary';
                        }
                    } else if (resultData.productBatchGetLinkInfoList && resultData.productBatchGetLinkInfoList.length > 0) {
                        const info = resultData.productBatchGetLinkInfoList[0];
                        if (info.offerPromotionLink && trackingLink === info.offerPromotionLink) {
                            linkType = 'Offer';
                            linkTypeClass = 'warning';
                        } else if (info.mmPromotionLink && trackingLink === info.mmPromotionLink) {
                            linkType = 'MM';
                            linkTypeClass = 'info';
                        } else if (info.dmPromotionLink && trackingLink === info.dmPromotionLink) {
                            linkType = 'DM';
                            linkTypeClass = 'secondary';
                        }
                    }
                }
                
                // Check if productName is actually a URL (originalUrl), don't display it
                const isProductNameUrl = productName && (productName.startsWith('http://') || productName.startsWith('https://'));
                
                if ((productName && !isProductNameUrl) || commissionRate) {
                    html += '<div class="product-info-card">';
                    if (productName && !isProductNameUrl) {
                        html += `
                            <div class="mb-2">
                                <i class="bi bi-bag me-1 text-primary"></i>
                                <strong>Sản phẩm:</strong> ${escapeHtml(productName)}
                            </div>
                        `;
                    }
                    if (commissionRate) {
                        const formattedRate = formatCommissionRate(commissionRate);
                        html += `
                            <div>
                                <i class="bi bi-percent me-1 text-success"></i>
                                <strong>Commission:</strong> 
                                <span class="badge bg-success">${escapeHtml(formattedRate)}</span>
                            </div>
                        `;
                    }
                    html += '</div>';
                }
                
                html += `
                    <div class="tracking-link-box">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <label class="form-label mb-0">
                                <i class="bi bi-link-45deg me-1 text-success"></i>
                                <strong>Tracking Link:</strong>
                                <span class="badge bg-${linkTypeClass} ms-2">${linkType}</span>
                            </label>
                            <button type="button" class="btn btn-sm btn-success" onclick="copyToClipboard('${escapeHtml(trackingLink)}')">
                                <i class="bi bi-clipboard me-1"></i>Copy
                            </button>
                        </div>
                        <div class="link-text">${escapeHtml(trackingLink)}</div>
                    </div>
                `;
                
                showToast('Tạo tracking link thành công!', 'success');
            } else {
                html += `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Không tìm thấy tracking link trong response.
                    </div>
                `;
            }
            
            // Always show Raw Response at the bottom
            html += `
                <details class="mt-3">
                    <summary class="text-muted" style="cursor: pointer;"><i class="bi bi-code-slash me-1"></i>Xem Raw API Response</summary>
                    <pre class="mt-2">${escapeHtml(JSON.stringify(data, null, 2))}</pre>
                </details>
            `;
            
            html += '</div>';
            container.innerHTML = html;
        }
        
        function renderAnalysisInfo(analysis) {
            if (!analysis || !analysis.type) return '';
            
            let html = '';
            
            // Input type badge
            const typeLabels = {
                'productId': { label: 'Product ID', class: 'product-type', icon: 'box' },
                'url': { label: 'URL (Page/Campaign)', class: 'url-type', icon: 'globe' },
                'offerId': { label: 'Offer ID', class: 'product-type', icon: 'tag' },
            };
            
            const typeInfo = typeLabels[analysis.type] || { label: analysis.type, class: '', icon: 'question-circle' };
            
            html += `
                <div class="input-type-badge ${typeInfo.class} mb-2">
                    <i class="bi bi-${typeInfo.icon} me-1"></i>
                    <strong>Input Type:</strong> ${typeInfo.label}
                </div>
            `;
            
            // Original input URL - always show if it's a URL type
            if (analysis.original_input && (analysis.type === 'url' || analysis.was_short_url || analysis.is_product_url)) {
                html += `
                    <div class="original-input-info mb-2">
                        <i class="bi bi-input-cursor me-1 text-secondary"></i>
                        <small><strong>URL gốc nhập vào:</strong></small>
                        <br>
                        <small class="text-muted" style="word-break: break-all;">${escapeHtml(analysis.original_input)}</small>
                    </div>
                `;
            }
            
            // Warning nếu resolve về homepage
            if (analysis.is_homepage_redirect) {
                html += `
                    <div class="alert alert-warning py-2 mb-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <small><strong>Cảnh báo:</strong> Link đã hết hạn hoặc redirect về homepage. Đang sử dụng URL gốc (đã clean tracking params).</small>
                    </div>
                `;
            }
            
            // Was short URL - show resolved URL (only if different from original and NOT homepage)
            if (analysis.was_short_url && analysis.resolved_url && analysis.resolved_url !== analysis.original_input && !analysis.is_homepage_redirect) {
                html += `
                    <div class="resolved-url-info mb-2">
                        <i class="bi bi-link-45deg me-1 text-warning"></i>
                        <small><strong>Short URL đã được resolve:</strong></small>
                        <br>
                        <small class="text-muted" style="word-break: break-all;">${escapeHtml(analysis.resolved_url)}</small>
                    </div>
                `;
            }
            
            // Cleaned URL info - show if tracking params were removed
            // Compare with resolved_url if short URL (and not homepage redirect), otherwise compare with original_input
            const urlToCompare = (analysis.was_short_url && !analysis.is_homepage_redirect) ? analysis.resolved_url : analysis.original_input;
            if (analysis.cleaned_url && urlToCompare && analysis.cleaned_url !== urlToCompare) {
                html += `
                    <div class="cleaned-url-info mb-2">
                        <i class="bi bi-check-circle me-1 text-success"></i>
                        <small><strong>Đã loại bỏ tracking params:</strong></small>
                        <br>
                        <small class="text-muted" style="word-break: break-all;">${escapeHtml(analysis.cleaned_url)}</small>
                    </div>
                `;
            }
            
            // Product ID extracted
            if (analysis.type === 'productId' && analysis.is_product_url) {
                html += `
                    <div class="extracted-id mb-2">
                        <i class="bi bi-hash me-1"></i>
                        <strong>Product ID:</strong> ${escapeHtml(analysis.value)}
                    </div>
                `;
            }
            
            return html;
        }
        
        // ==============================================================================
        // CONVERSION REPORT HANDLER - AUTO LOAD ALL DATA
        // ==============================================================================
        
        document.getElementById('conversionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            conversionState = {
                allData: [],
                filteredData: [],
                currentPage: 1,
                perPage: parseInt(document.getElementById('perPage').value),
                totalPages: 1,
                searchTerm: '',
                dateStart: document.getElementById('dateStart').value,
                dateEnd: document.getElementById('dateEnd').value,
            };
            
            await loadAllConversionData();
        });
        
        async function loadAllConversionData() {
            const btn = document.getElementById('btnConversion');
            btn.classList.add('btn-loading');
            showLoading('Đang tải dữ liệu...', 'Đang lấy tất cả đơn hàng từ API', true);
            
            const dateStart = document.getElementById('dateStart').value;
            const dateEnd = document.getElementById('dateEnd').value;
            const offerId = document.getElementById('offerId').value;
            const mmPartnerFlag = document.getElementById('mmPartnerFlag').checked ? '1' : '0';
            
            let allConversions = [];
            let currentPage = 1;
            let hasMore = true;
            
            try {
                while (hasMore) {
                    updateLoadingProgress(allConversions.length, `Đang tải trang ${currentPage}... (${allConversions.length} đơn)`);
                    
                    const formData = new FormData();
                    formData.append('action', 'get_conversion_page');
                    formData.append('date_start', dateStart);
                    formData.append('date_end', dateEnd);
                    formData.append('page', currentPage);
                    formData.append('offer_id', offerId);
                    formData.append('mm_partner_flag', mmPartnerFlag);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (!result.success) {
                        hideLoading();
                        btn.classList.remove('btn-loading');
                        renderConversionError(result);
                        return;
                    }
                    
                    const conversions = result.conversions || [];
                    allConversions = [...allConversions, ...conversions];
                    hasMore = result.has_more;
                    currentPage++;
                    
                    if (currentPage > 50) {
                        console.log('Reached max pages limit');
                        break;
                    }
                }
                
                hideLoading();
                btn.classList.remove('btn-loading');
                
                conversionState.allData = allConversions;
                conversionState.filteredData = [...allConversions];
                conversionState.totalPages = Math.ceil(allConversions.length / conversionState.perPage) || 1;
                conversionState.currentPage = 1;
                
                renderConversionResult();
                showToast(`Đã tải xong ${allConversions.length} đơn hàng!`, 'success');
                
            } catch (error) {
                hideLoading();
                btn.classList.remove('btn-loading');
                showToast('Có lỗi xảy ra: ' + error.message, 'error');
            }
        }
        
        function filterConversions(searchTerm) {
            conversionState.searchTerm = searchTerm.toLowerCase().trim();
            
            if (!conversionState.searchTerm) {
                conversionState.filteredData = [...conversionState.allData];
            } else {
                conversionState.filteredData = conversionState.allData.filter(c => {
                    const orderId = (c.orderId || '').toLowerCase();
                    const skuName = (c.skuName || '').toLowerCase();
                    const brandName = (c.brandName || '').toLowerCase();
                    
                    return orderId.includes(conversionState.searchTerm) ||
                           skuName.includes(conversionState.searchTerm) ||
                           brandName.includes(conversionState.searchTerm);
                });
            }
            
            conversionState.totalPages = Math.ceil(conversionState.filteredData.length / conversionState.perPage) || 1;
            conversionState.currentPage = 1;
            
            renderConversionTable();
        }
        
        function changePerPage(value) {
            conversionState.perPage = parseInt(value);
            conversionState.totalPages = Math.ceil(conversionState.filteredData.length / conversionState.perPage) || 1;
            conversionState.currentPage = 1;
            renderConversionTable();
        }
        
        function goToPage(page) {
            if (page < 1 || page > conversionState.totalPages) return;
            conversionState.currentPage = page;
            renderConversionTable();
        }
        
        function renderConversionError(result) {
            const container = document.getElementById('conversionResult');
            container.innerHTML = `
                <div class="result-container">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Lỗi:</strong> ${escapeHtml(result.error || 'Unknown error')}
                        ${result.error_code ? `<br><small>Error Code: ${escapeHtml(result.error_code)}</small>` : ''}
                    </div>
                </div>
            `;
            showToast('Không thể lấy báo cáo!', 'error');
        }
        
        function renderConversionResult() {
            const container = document.getElementById('conversionResult');
            const conversions = conversionState.allData;
            
            let validOrderCount = 0;
            let totalPayout = 0;
            let totalOrderAmt = 0;
            let rejectedCount = 0;
            let returnedCount = 0;
            let cancelledCount = 0;
            
            conversions.forEach(c => {
                const excludeType = isExcludedOrder(c);
                
                if (excludeType === 'rejected') {
                    rejectedCount++;
                    return;
                }
                if (excludeType === 'returned') {
                    returnedCount++;
                    return;
                }
                if (excludeType === 'cancelled') {
                    cancelledCount++;
                    return;
                }
                
                validOrderCount++;
                totalPayout += parseFloat(c.estPayout || 0);
                totalOrderAmt += parseFloat(c.orderAmt || 0);
            });
            
            const excludedCount = rejectedCount + returnedCount + cancelledCount;
            
            let html = '<div class="result-container"><hr class="my-4">';
            
            html += `
                <div class="row mb-3">
                    <div class="col-6 col-md-3 mb-2">
                        <div class="stats-card">
                            <i class="bi bi-cart-check fs-3"></i>
                            <h3>${validOrderCount}</h3>
                            <small>Đơn hợp lệ</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="stats-card green">
                            <i class="bi bi-cash-stack fs-3"></i>
                            <h3>${formatNumber(Math.round(totalPayout))}</h3>
                            <small>Hoa hồng ước tính</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="stats-card orange">
                            <i class="bi bi-bag-check fs-3"></i>
                            <h3>${formatNumber(Math.round(totalOrderAmt))}</h3>
                            <small>Doanh số</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="stats-card red">
                            <i class="bi bi-x-circle fs-3"></i>
                            <h3>${excludedCount}</h3>
                            <small>Hủy/Hoàn</small>
                        </div>
                    </div>
                </div>
            `;
            
            if (excludedCount > 0) {
                const excludeDetails = [];
                if (rejectedCount > 0) excludeDetails.push(`${rejectedCount} Rejected`);
                if (returnedCount > 0) excludeDetails.push(`${returnedCount} Returned`);
                if (cancelledCount > 0) excludeDetails.push(`${cancelledCount} Cancelled`);
                
                html += `
                    <div class="alert alert-info py-2 mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>
                            Đã loại trừ <strong>${excludedCount}</strong> đơn khỏi thống kê:
                            ${excludeDetails.join(', ')}
                            | Tổng số đơn: ${conversions.length}
                        </small>
                    </div>
                `;
            }
            
            // Daily Chart
            if (conversions.length > 0) {
                html += `
                    <div class="daily-chart-container">
                        <h6><i class="bi bi-bar-chart-line me-2"></i>Thống kê theo ngày</h6>
                        <div class="chart-wrapper">
                            <canvas id="dailyChart"></canvas>
                        </div>
                    </div>
                `;
            }
            
            if (conversions.length > 0) {
                html += `
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <div class="search-box">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" class="form-control" id="conversionSearch" 
                                       placeholder="Tìm theo mã đơn hàng, tên sản phẩm..."
                                       oninput="filterConversions(this.value)">
                                <i class="bi bi-x-circle clear-search" id="clearSearch" onclick="clearSearch()"></i>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="per-page-selector d-inline-flex">
                                <label class="me-2 text-muted small align-self-center">Hiển thị:</label>
                                <select class="form-select form-select-sm" style="width: 80px;" onchange="changePerPage(this.value)">
                                    <option value="20" ${conversionState.perPage === 20 ? 'selected' : ''}>20</option>
                                    <option value="50" ${conversionState.perPage === 50 ? 'selected' : ''}>50</option>
                                    <option value="100" ${conversionState.perPage === 100 ? 'selected' : ''}>100</option>
                                    <option value="200" ${conversionState.perPage === 200 ? 'selected' : ''}>200</option>
                                </select>
                                <span class="ms-2 text-muted small align-self-center">/ trang</span>
                            </div>
                        </div>
                    </div>
                `;
                
                html += '<div id="conversionTableContainer"></div>';
            } else {
                html += `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Không có dữ liệu chuyển đổi trong khoảng thời gian này.
                    </div>
                `;
            }
            
            html += '</div>';
            container.innerHTML = html;
            
            if (conversions.length > 0) {
                renderConversionTable();
                // Render chart after DOM is ready
                setTimeout(() => {
                    renderDailyChart(conversions, conversionState.dateStart, conversionState.dateEnd);
                }, 100);
            }
        }
        
        function renderConversionTable() {
            const container = document.getElementById('conversionTableContainer');
            if (!container) return;
            
            const filteredData = conversionState.filteredData;
            const startIndex = (conversionState.currentPage - 1) * conversionState.perPage;
            const endIndex = startIndex + conversionState.perPage;
            const pageData = filteredData.slice(startIndex, endIndex);
            const searchTerm = conversionState.searchTerm;
            
            const clearBtn = document.getElementById('clearSearch');
            if (clearBtn) {
                clearBtn.classList.toggle('show', searchTerm.length > 0);
            }
            
            if (filteredData.length === 0) {
                container.innerHTML = `
                    <div class="no-results">
                        <i class="bi bi-search"></i>
                        <p>Không tìm thấy kết quả phù hợp với "${escapeHtml(searchTerm)}"</p>
                    </div>
                `;
                return;
            }
            
            let html = `
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Mã đơn hàng</th>
                                <th>Sản phẩm</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Giá trị</th>
                                <th class="text-end">% HH</th>
                                <th class="text-end">Hoa hồng</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            pageData.forEach((c, index) => {
                const isExcluded = isExcludedOrder(c);
                const status = c.status || 'N/A';
                const validity = c.validity || '';
                
                let statusClass = 'secondary';
                if (status === 'Fulfilled' || status === 'Delivered') {
                    statusClass = 'success';
                } else if (status.toLowerCase().includes('pending')) {
                    statusClass = 'warning';
                } else if (isExcluded) {
                    statusClass = 'danger';
                }
                
                const skuName = c.skuName || 'N/A';
                const displayName = skuName.length > 40 ? skuName.substring(0, 40) + '...' : skuName;
                const rate = formatCommissionRate(c.commissionRate || 0);
                const globalIndex = startIndex + index + 1;
                
                html += `
                    <tr class="${isExcluded ? 'excluded-row' : ''}">
                        <td>${globalIndex}</td>
                        <td><small class="text-muted">${highlightText(c.orderId || 'N/A', searchTerm)}</small></td>
                        <td style="max-width: 180px;">
                            <small class="d-block text-truncate" title="${escapeHtml(skuName)}">${highlightText(displayName, searchTerm)}</small>
                            <small class="text-muted">${highlightText(c.brandName || '', searchTerm)}</small>
                        </td>
                        <td>
                            <span class="badge bg-${statusClass}">${escapeHtml(status)}</span>
                            ${validity && validity.toLowerCase() !== 'valid' ? `<br><small class="text-${isExcluded ? 'danger' : 'warning'}">${escapeHtml(validity)}</small>` : ''}
                        </td>
                        <td class="text-end">${formatNumber(Math.round(parseFloat(c.orderAmt || 0)))}</td>
                        <td class="text-end">${rate}</td>
                        <td class="text-end ${isExcluded ? '' : 'text-success fw-bold'}">${formatNumber(Math.round(parseFloat(c.estPayout || 0)))}</td>
                        <td><small>${escapeHtml(formatDateTime(c.conversionTime || c.fulfilledTime || 'N/A'))}</small></td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            
            const totalPages = conversionState.totalPages;
            const currentPage = conversionState.currentPage;
            
            html += `
                <div class="pagination-container">
                    <div class="pagination-info">
                        Hiển thị ${startIndex + 1} - ${Math.min(endIndex, filteredData.length)} / ${filteredData.length} kết quả
                        ${searchTerm ? `(lọc từ ${conversionState.allData.length} đơn)` : ''}
                    </div>
            `;
            
            if (totalPages > 1) {
                html += `
                    <div class="pagination-controls">
                        <button class="btn btn-sm btn-outline-secondary" onclick="goToPage(1)" ${currentPage === 1 ? 'disabled' : ''}>
                            <i class="bi bi-chevron-double-left"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <span class="page-number">${currentPage} / ${totalPages}</span>
                        <button class="btn btn-sm btn-outline-secondary" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                            <i class="bi bi-chevron-right"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="goToPage(${totalPages})" ${currentPage === totalPages ? 'disabled' : ''}>
                            <i class="bi bi-chevron-double-right"></i>
                        </button>
                    </div>
                `;
            }
            
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        function clearSearch() {
            const searchInput = document.getElementById('conversionSearch');
            if (searchInput) {
                searchInput.value = '';
                filterConversions('');
            }
        }
    </script>
</body>
</html>