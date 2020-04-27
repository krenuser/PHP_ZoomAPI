<?php

namespace App\Classes;

class ZoomAPI {
    private $ch;
    private $bearer;
    private $lastHeaders;
    private $tokenData;
    private $tokenFilename;
    private $clientId           = '';
    private $clientSecret       = '';
    private $redirectURI        = '';
    private $curlLastError      = [];

    // Zoom API base url
    const BASE_URL = 'https://api.zoom.us/v2';

    // CURL method constants
    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';
    const METHOD_PATCH  = 'PATCH';
    const METHOD_PUT    = 'PUT';
    const METHOD_DELETE = 'DELETE';

    // Debug level constants
    const DEBUG_VERBOSE  = 0;
    const DEBUG_INFO     = 1;
    const DEBUG_CRITICAL = 2;
    const DEBUG_NONE     = 9;

    // Zoom user types (createUser method)
    const USER_TYPE_BASIC = 1;
    const USER_TYPE_LICENSED = 2;
    const USER_TYPE_ONPREM = 3;

    // Zoom create type (createUser method)
    const ACTION_CREATE     = 'create';             // send user an EMail with activation link
    const ACTION_AUTOCREATE = 'autoCreate';         // create user with email and password, no EMail, manual activation

    // Picture argument format
    const PICTURE_FILENAME = 'filename';            // $picture is file name
    const PICTURE_CONTENT  = 'content';             // $picture is binary picture file content

    // Meeting types
    const MEETING_UPCOMING = 'upcoming';
    const MEETING_PAST     = 'past';
    const MEETING_PAST_ONE = 'pastOne';

    // Create/update meeting settings > registration type constants ['settings' => ['registration_type' => ... ]]
    const REGISTRATION_TYPE_ONCE      = 1;
    const REGISTRATION_TYPE_EACH_TIME = 2;
    const REGISTRATION_TYPE_SELECTIVE = 3;

    // Create/update meeting settings > auto recording constants ['settings' => ['auto_recording' => ... ]]
    const AUTORECORDING_LOCAL    = 'local';
    const AUTORECORDING_CLOUD    = 'cloud';
    const AUTORECORDING_DISABLED = 'none';

    // Create/update meeting settings > audio constants ['settings' => ['audio' => ... ]]
    const AUDIO_VOIP      = 'voip';
    const AUDIO_TELEPHONY = 'telephony';
    const AUDIO_BOTH      = 'both';

    // Add/Delete group members userId type
    const ID_ZOOM         = 'zoom_id';
    const ID_EMAIL        = 'email';


    private $debugLevel         = ZoomAPI::DEBUG_VERBOSE;
    private $requestLogFilename;

    /**
     * ZoomAPI constructor.
     *
     * @param $tokenFilename
     * @param $clientId
     * @param $clientSecret
     * @param $redirectURI
     * @param bool $autoRefreshToken
     */
    public function __construct($tokenFilename, $clientId, $clientSecret, $redirectURI, $autoRefreshToken = false) {
        $this->ch = curl_init();

        $this->tokenFilename = $tokenFilename;
        $this->clientId      = $clientId;
        $this->clientSecret  = $clientSecret;
        $this->redirectURI   = $redirectURI;

        $this->tokenData = $this->getTokenInfo();
        if($autoRefreshToken && isset($this->tokenData['expires_ts']) && $this->tokenData['expires_ts'] < time()) {
            // token expired, trying to refresh automatically
            $this->refreshToken();
            $this->tokenData = $this->getTokenInfo();
        }  // doing one time to not infinite-loop

        if(isset($this->tokenData['data']) && isset($this->tokenData['data']['access_token'])) {
            $this->bearer = $this->tokenData['data']['access_token'];
        }

        $this->log(self::DEBUG_VERBOSE, __METHOD__, 'Bearer: '.$this->bearer);

        if($this->bearer == '') {
            $this->log(self::DEBUG_CRITICAL, __METHOD__, 'No bearer!');
        }

        $this->requestLogFilename = APP_ROOT.'/logs/sendRequest.'.date('Y-m-d').'.bin';
    }

    /**
     * Save token info to file (for further use)
     *
     * @param array $tokenData
     * @return bool
     */
    public function setTokenInfo($tokenData) {
        if(is_array($tokenData) && isset($tokenData['access_token'])) {
            $this->log(self::DEBUG_VERBOSE, __METHOD__, 'Save token info: '.json_encode($tokenData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            // write token data to JSON file
            @file_put_contents(
                $this->tokenFilename,
                json_encode([
                    'created'    => date('Y-m-d H:i:s'),
                    'created_ts' => time(),
                    'expires_ts' => time() + $tokenData['expires_in'],
                    'data'       => $tokenData,
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
            return true;
        }
        return false;
    }

    /**
     * Get saved token info
     *
     * @return array|mixed
     */
    public function getTokenInfo() {
        if(file_exists($this->tokenFilename)) {
            return json_decode(file_get_contents($this->tokenFilename), true);
        }
        return [];
    }

    /**
     * Return last received headers error
     *
     * @return array
     */
    public function getLastHeaders() {
        return $this->lastHeaders;
    }

    /**
     * Request access token by code, returned from Zoom Auth
     *
     * @param string $code
     * @return bool - if operation succeeded
     */
    public function requestToken($code) {
        $curlOptions = [
            CURLOPT_URL            => 'https://zoom.us/oauth/token',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER         => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => 'grant_type=authorization_code&code=' . $code . '&redirect_uri=' . $this->getRedirectURI(),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic '.base64_encode($this->getClientId().':'.$this->getClientSecret()),
            ],
        ];
        curl_setopt_array($this->ch, $curlOptions);

        if($this->getDebugLevel() >= self::DEBUG_VERBOSE) {
            @file_put_contents(
                $this->requestLogFilename, "=== requestToken ===============================".PHP_EOL.
                json_encode($this->translateCurlOptions($curlOptions), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL.
                    '-- /requestToken ----------------------------------'.PHP_EOL,
                FILE_APPEND
            );
        }

        $response = curl_exec($this->ch);
        if($this->getDebugLevel() >= self::DEBUG_VERBOSE) {
            @file_put_contents($this->requestLogFilename, $response.PHP_EOL,FILE_APPEND);
        }

        if(($curlErrNo = curl_errno($this->ch)) == 0) {
            $this->setTokenInfo(json_decode($response, true));
            return true;
        }

        $this->curlLastError = [
            'code' => $curlErrNo,
            'message' => curl_error($this->ch),
        ];

        return false;
    }

    /**
     * Send built ZoomHttpRequest to Zoom API
     *
     * @param ZoomHttpRequest $request
     * @param bool $autoJsonDecode - auto decode response JSON data
     * @param bool $autoRefreshToken - works only if $autoJsonDecode is true: automatically refresh token on JSON response {code: 124, message: "Access token expired"}
     * @return mixed
     */
    public function sendRequest(ZoomHttpRequest $request, $autoJsonDecode = true, $autoRefreshToken = true) {
        $path        = $request->getUrl();
        $headers     = $request->getHeaders();
        $data        = $request->getBody();
        $method      = $request->getMethod();
        $params      = $request->getParams();
        $miscOptions = $request->getMiscOptions();

        $hasAuthorizationHeader = false;
        if(substr($path, 0, 1) != '/') {
            $path = '/' . $path;
        }

        // -- checking headers for authorization one
        foreach($headers as $header) {
            if(stripos($header, 'authorization') !== FALSE) {
                $hasAuthorizationHeader = true;
                break;
            }
        }
        // -- if we didn't find auth header, add it
        if(!$hasAuthorizationHeader) {
            $headers[] = 'Authorization: Bearer '.$this->bearer;
        }

        // -- setting mandatory curl params
        $curlOptions = [
            CURLOPT_URL            => self::BASE_URL . $path . (count($params) > 0 ? ('?' . http_build_query($params)) : ''),
            CURLOPT_HEADER         => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POST           => $method <> 'GET',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_RETURNTRANSFER => true,
        ];
        if($method != 'GET') {
            $curlOptions[CURLOPT_POSTFIELDS] = $data;
        }
        if(!in_array($method, ['GET', 'POST'])) {
            $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
        }
        curl_setopt_array($this->ch, $curlOptions);

        // -- проставляем явно переданные опции CURL
        if(count($miscOptions)) {
            curl_setopt_array($this->ch, $miscOptions);
        }

        // logging request data
        if($this->getDebugLevel() >= self::DEBUG_VERBOSE) {
            @file_put_contents(
                $this->requestLogFilename,
                '=== sendRequest ==============================================================' . PHP_EOL .
                json_encode($this->translateCurlOptions($curlOptions), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL .
                '--- /sendRequest --------------------------------------------------------------' . PHP_EOL .
                PHP_EOL . PHP_EOL,
                FILE_APPEND
            );
        }

        $curlResponse = curl_exec($this->ch);

        // -- cutting off intermediate HTTP-status HTTP/1.1 100 Continue that making troubles...
        if(preg_match("/HTTP\\/.*?100 Continue/i", $curlResponse)) {
            $curlResponse = preg_replace("/^HTTP\\/.*?100 Continue\r\n\r\n/i", '', $curlResponse);
        }

        // logging response data
        if($this->getDebugLevel() >= self::DEBUG_VERBOSE) {
            file_put_contents($this->requestLogFilename, $curlResponse . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);
        }

        // splitting responses to header and body parts
        $responsePart = explode("\r\n\r\n", $curlResponse, 2);
        $headerLines  = explode("\r\n", $responsePart[0]);
        $responseBody = $responsePart[1];

        $resultingHeaders = [];
        foreach($headerLines as $headerLine) {
            $headerParts = explode(': ', $headerLine, 2);
            $matches = [];

            // -- is HTTP status header
            if(preg_match("/^HTTP\\/[^ ]+ ([0-9]+)(.*?)/i", $headerLine, $matches)) {
                $resultingHeaders['HTTP_STATUS']      = $matches[1];
                if(isset($matches[2]))     // текста статуса может не быть
                    $resultingHeaders['HTTP_STATUS_TEXT'] = @$matches[2];
                else
                    $resultingHeaders['HTTP_STATUS_TEXT'] = '';
            }
            else {
                if ($headerParts[0] == 'Set-Cookie') { // -- saving cookie headers as an array - ['Set-Cookie' => ['xxx: yyy', 'zzz: kkk', ...]]
                    $resultingHeaders['Set-Cookie'][] = $headerParts[1];
                }
                else {
                    $resultingHeaders[$headerParts[0]] = $headerParts[1];
                }
            }
        }
        $this->lastHeaders = $resultingHeaders;

        if($autoJsonDecode) {
            $responseBody = json_decode($responseBody, true);
            if(isset($responseBody['code']) && $responseBody['code'] == 124 && $autoRefreshToken) {
                $this->log(self::DEBUG_INFO, __METHOD__, 'Code 124 - need to refresh token');
                $this->refreshToken();

                $this->log(self::DEBUG_VERBOSE, __METHOD__, 'Retry request');
                return $this->sendRequest($request, $autoJsonDecode, false);
            }
        }

        return $responseBody;
    }

    public function __destruct() {
        curl_close($this->ch);
    }

    /**
     * Refresh expired token
     *
     * @return bool
     */
    public function refreshToken() {
        $this->log(self::DEBUG_INFO, __METHOD__, 'refreshToken()');

        $curlOptions = [
            CURLOPT_URL            => 'https://zoom.us/oauth/token?grant_type=refresh_token&refresh_token='.$this->tokenData['data']['refresh_token'],
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . base64_encode($this->getClientId() . ':' . $this->getClientSecret()),
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADER         => true,
        ];
        curl_setopt_array($this->ch, $curlOptions);

        if($this->debugLevel >= self::DEBUG_VERBOSE) {
            file_put_contents($this->requestLogFilename,
                '=== refreshToken ==============================================================' . PHP_EOL .
                json_encode($this->translateCurlOptions($curlOptions), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL .
                '--- /refreshToken -------------------------------------------------------------' . PHP_EOL. PHP_EOL . PHP_EOL,
                FILE_APPEND
            );
        }

        $response = curl_exec($this->ch);

        if(($curlErrNo = curl_errno($this->ch)) == 0) {
            // -- cutting off intermediate HTTP-status HTTP/1.1 100 Continue that making troubles...
            if (preg_match("/HTTP\\/.*?100 Continue/i", $response)) {
                $response = preg_replace("/^HTTP\\/.*?100 Continue\r\n\r\n/i", '', $response);
            }
            if ($this->getDebugLevel() >= self::DEBUG_VERBOSE) {
                file_put_contents($this->requestLogFilename, $response . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);
            }

            $responseParts = explode("\r\n\r\n", $response, 2);  // s[0] = headers, s[1] = body
            $headerLines   = explode("\r\n", $responseParts[0]);
            $responseBody  = $responseParts[1];

            $resultingHeaders = [];
            foreach ($headerLines as $headerLine) {
                $headerParts = explode(': ', $headerLine, 2);
                $matches     = [];

                // -- is this is HTTP status header
                if (preg_match("/^HTTP\\/[^ ]+ ([0-9]+)(.*?)/i", $headerLine, $matches)) {
                    $resultingHeaders['HTTP_STATUS'] = $matches[1];
                    if (isset($matches[2]))     // status text can be absent
                        $resultingHeaders['HTTP_STATUS_TEXT'] = @$matches[2];
                    else
                        $resultingHeaders['HTTP_STATUS_TEXT'] = '';
                } else {
                    if ($headerParts[0] == 'Set-Cookie') { // -- save cookie headers as an array
                        $resultingHeaders['Set-Cookie'][] = $headerParts[1];
                    } else {
                        $resultingHeaders[$headerParts[0]] = $headerParts[1];
                    }
                }
            }
            $this->lastHeaders = $resultingHeaders;
            $responseBody      = json_decode($responseBody, true);

            if ($setTokenInfoResult = $this->setTokenInfo($responseBody)) {
                $this->bearer = $this->getTokenInfo()['data']['access_token'];
            }
            return $setTokenInfoResult;
        }
        else {
            $this->curlLastError = [
                'code'    => $curlErrNo,
                'message' => curl_error($this->ch),
            ];
            return false;
        }
    }

    /**
     * Page all users and get extended info if necessary
     *
     * @param bool $bGetExtInfo - get detailed info for each user
     * @return array
     */
    public function getAllUsers($bGetExtInfo = false, $bEchoProgress = false) {
        $request = new ZoomHttpRequest();
        $request
            ->setMethod(ZoomAPI::METHOD_GET)
            ->setUrl('/users');

        $allUsers = [];

        $pageNumber = 0;
        do {
            $request->setParams(['page_number' => ++$pageNumber]);
            $result = $this->sendRequest($request);

            $pageCount = intval($result['page_count']);
            $pageNumber = intval($result['page_number']);

            foreach($result['users'] as $user) {
                $allUsers[] = $user;
            }
        }
        while($pageCount > $pageNumber);

        if($bGetExtInfo) {
            $resUsers = [];
            $totalCount = count($allUsers);
            $currentIdx = 0;
            foreach($allUsers as $user) {
                if($bEchoProgress) {
                    // for not to have HTTP gateway timeout we should echo smth to STDOUT
                    echo strval(++$currentIdx).' / '.strval($totalCount).". Getting detailed info on {$user['email']} \n"; flush();
                }
                $resUsers[$user['id']] = $this->getUserInfo($user['id']);
            }
            $allUsers = $resUsers;
        }

        return $allUsers;
    }

    /**
     * Create new user in current Zoom account
     *
     * @param $email
     * @param $firstName
     * @param $lastName
     * @param null $password
     * @param string $action
     * @param int $userType
     * @return bool|mixed|string
     */
    public function createUser($email, $firstName, $lastName, $password = null, $action = self::ACTION_CREATE, $userType = self::USER_TYPE_BASIC) {
        if($action)
        $body = [
            'action' => 'create',
            'user_info' => [
                'type'       => $userType,
                'email'      => $email,
                'first_name' => $firstName,
                'last_name'  => $lastName,
            ]
        ];
        if($action == 'autoCreate') {
            $body['user_info']['password'] = $password;
        }
        $request = (new ZoomHttpRequest())
            ->setMethod(self::METHOD_POST)
            ->setUrl('/users')
            ->setHeaders([
                'Content-Type: application/json',
            ])
            ->setBody(json_encode($body))
            ->setParams([]);

        $result = $this->sendRequest($request);

        return isset($result['id']);
    }


    /**
     * Checks if email exists in Zoom
     *
     * @param string $email
     * @return bool
     */
    public function isEmailExists($email) {
        $request = (new ZoomHttpRequest())
            ->setMethod(self::METHOD_GET)
            ->setUrl('/users/email')
            ->setHeaders(['Content-Type: application/json'])
            ->setParams(['email' => $email]);

        $result = $this->sendRequest($request);

        return (bool)$result['existed_email'];
    }

    /**
     * Update Zoom user data
     *
     * @param $userId
     * @param array $params
     * @return mixed
     */
    public function updateUser($userId, $params = []) {
        $request = (new ZoomHttpRequest())
            ->setMethod(self::METHOD_PATCH)
            ->setUrl('/users/'.$userId)
            ->setHeaders([
                'Content-Type: application/json',
            ])
            ->setBody(json_encode($params, JSON_UNESCAPED_UNICODE))
            ->setParams(['login_type' => 100]);

        return $this->sendRequest($request);
    }

    /**
     * Get detailed Zoom user info by Zoom user ID
     *
     * @param string $userId
     * @return mixed
     */
    public function getUserInfo($userId) {
        $request = new ZoomHttpRequest();
        $request
            ->setMethod(self::METHOD_GET)
            ->setUrl('/users/'.$userId)
            ->setParams([
                'login_type' => 100,            // Zoom ID
            ]);

        return $this->sendRequest($request);
    }

    /**
     * Add new member to group by ID
     *
     * @param string $groupId - Zoom group ID
     * @param string $userId - Zoom ID or email (depending on $idType arg)
     * @param string $idType - ZoomAPI::ID_* - type of $userId identifier
     * @return mixed
     */
    public function addGroupMember($groupId, $userId, $idType = ZoomAPI::ID_ZOOM) {
        $request = new ZoomHttpRequest();
        $request
            ->setMethod(self::METHOD_POST)
            ->setUrl("/groups/{$groupId}/members")
            ->setHeaders(['Content-Type: application/json'])
            ->setBody(json_encode([
                'members' => [
                    ($idType == ZoomAPI::ID_ZOOM ? ['id' => $userId,] : ['email' => $userId, ])
                ],
            ]));

        return $this->sendRequest($request);
    }

    /**
     * Update user picture profile
     *
     * @param $userId
     * @param $picture - picture absolute filename or picture content (depends on $pictureFormat)
     * @param string $pictureFormat - PICTURE_FILENAME (absoulute filename for picture) | PICTURE_CONTENT (picture binary content)
     * @param string $extension - used only with PICTURE_CONTENT
     * @return array
     */
    public function updateUserPicture($userId, $picture, $pictureFormat = self::PICTURE_FILENAME, $extension = null) {
        // generating random MIME boundary name
        $boundaryName = '';
        for($i = 0; $i < 30; $i++) {
            $boundaryName .= strval(mt_rand(0, 9));
        }

        $fileContent = '';
        if($pictureFormat == self::PICTURE_FILENAME) {
            $fileContent = file_get_contents($picture);
            preg_match("/\\.(.*)\$/i", $picture, $m);
            $extension = $m[1];
        }
        elseif($pictureFormat == ZoomAPI::PICTURE_CONTENT) {
            $fileContent = $picture;
        }
        $contentSize = strlen($fileContent);

        $body = "--{$boundaryName}\r\n".
            "Content-Disposition: form-data; name=\"pic_file\"; filename=\"avatar.png\"\r\n".
            "Content-Type: image/{$extension}}\r\n".
            "Content-Length: {$contentSize}\r\n".
            "\r\n".
            $fileContent."\r\n" .
            "--{$boundaryName}--";

        $request = new ZoomHttpRequest();
        $request
            ->setMethod(ZoomAPI::METHOD_POST)
            ->setUrl("/users/{$userId}/picture")
            // ->setMiscOptions([CURLOPT_SAFE_UPLOAD => false, ])
            ->setHeaders([
                'Content-Type: multipart/form-data; boundary='.$boundaryName,
                'Content-Length: '. strlen($body),
                'Connection: close',
            ])
            ->setBody($body);

        return $this->sendRequest($request, true);
    }

    /**
     * List all user meetings
     *
     * @param string $userId
     * @param string $type
     * @return array
     */
    public function listMeetings($userId, $type = self::MEETING_UPCOMING) {
        $request = new ZoomHttpRequest();
        $request
            ->setMethod(self::METHOD_GET)
            ->setUrl('/users/'.$userId.'/meetings');


        $resMeetings = [];
        $pageNumber = 0;
        do
        {
            $pageNumber++;

            $request->setParams([
                'type'        => $type,
                'page_number' => $pageNumber,
                'page_size'   => 50,
            ]);
            $result = $this->sendRequest($request, true);

            $meetings = $result['meetings'];
            foreach($meetings as $meeting) {
                $resMeetings[] = $meeting;
            }

            $pageNumber = $result['page_number'];
            $pageCount  = $result['page_count'];

        }
        while($pageNumber < $pageCount);

        return $resMeetings;
    }

    /**
     * List all registrants for meeting using Meeting ID
     *
     * @param string $meetingId
     * @param string $type
     * @return array
     */
    public function listMeetingParticipants($meetingId, $type = ZoomAPI::METRICS_MEETING_LIVE) {
        $request = new ZoomHttpRequest();
        $params = [
            'page_size' => 20,
            'type' => $type,
        ];
        if(strpos($meetingId, '/')) {
            $meetingId = str_replace('/', '//', $meetingId);
        }
        $request
            ->setMethod(ZoomAPI::METHOD_GET)
            ->setUrl("/metrics/meetings/{$meetingId}/participants");

        $resultParticipants = [];

        $params['next_page_token'] = '';
        do {
            $request->setParams($params);

            $result = $this->sendRequest($request, true);

            if(isset($result['participants']) && is_array($result['participants'])) {
                foreach($result['participants'] as $participant) {
                    $resultParticipants[] = $participant;
                }
            }

            $params['next_page_token'] = @$result['next_page_token'];
        }
        while($params['next_page_token'] != '');

        return $resultParticipants;
    }


    /**
     * GET /past_meetings/{uuid}
     *
     * @param $meetingUUID
     * @return array
     */
    public function getPastMeetingDetails($meetingUUID) {
        $request = new ZoomHttpRequest();
        $meetingUUID = str_replace('/', '//', $meetingUUID);
        $request
            ->setMethod(self::METHOD_GET)
            ->setUrl("/past_meetings/{$meetingUUID}");

        return (array)$this->sendRequest($request, true);
    }

    /**
     * GET /past_meetings/{uuid}/participants
     *
     * @param $meetingUUID
     * @return array
     */
    public function getPastMeetingParticipants($meetingUUID) {
        $request = new ZoomHttpRequest();
        $meetingUUID = str_replace('/', '//', $meetingUUID);
        $request
            ->setMethod(self::METHOD_GET)
            ->setUrl("/past_meetings/{$meetingUUID}/participants");
        $params = [
            'page_size'       => 50,
            'next_page_token' => '',
        ];

        $resParticipants = [];

        do {
            $request->setParams($params);
            $result = $this->sendRequest($request, true);
            $params['next_page_token'] = isset($result['next_page_token']) ? $result['next_page_token'] : '';

            if(isset($result['participants']) && is_array($result['participants'])) {
                foreach($result['participants'] as $participant) {
                    if($participant['user_email']) {
                        $resParticipants[$participant['user_email']] = $participant;
                    }
                    elseif($participant['name']) {
                        $resParticipants[$participant['name']] = $participant;
                    }
                    else {
                        $resParticipants[] = $participant;
                    }
                }
            }

        } while($params['next_page_token'] != '');

        return $resParticipants;
    }

    /**
     * GET /metrics/meetings
     *
     * @param string $type
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function metricsMeetings($dateFrom, $dateTo, $type = ZoomAPI::METRICS_MEETING_LIVE) {
        $request = new ZoomHttpRequest();
        $request
            ->setMethod(ZoomAPI::METHOD_GET)
            ->setUrl('/metrics/meetings');

        $resultMeetings = [];
        $nextPageToken = '';
        do  {
            $request->setParams([
                'type'            => $type,
                'from'            => $dateFrom,
                'to'              => $dateTo,
                'page_size'       => 50,
                'next_page_token' => $nextPageToken,
            ]);
            $result = $this->sendRequest($request);

            if(isset($result['meetings']) && is_array($result['meetings'])) {
                foreach($result['meetings'] as $meeting) {
                    $resultMeetings[] = $meeting;
                }
            }

            $nextPageToken = @$result['next_page_token'];
        } while($nextPageToken != '');

        return $resultMeetings;
    }

    /**
     * GET meetings details (dashboards)
     *
     * @param string $meetingId - ID or UUID
     * @param string $meetingType - METRICS_MEETING_*
     * @return array
     */
    public function getMeetingDetails($meetingId, $meetingType = ZoomAPI::METRICS_MEETING_PAST) {
        $request = new ZoomHttpRequest();
        $meetingId = str_replace("/", '//', $meetingId);
        $request
            ->setMethod(ZoomAPI::METHOD_GET)
            ->setUrl('/metrics/meetings/'.$meetingId)
            ->setParams(['type' => $meetingType, ]);


        return (array) $this->sendRequest($request);
    }

    /**
     * Get all groups in account
     *
     * @return array - ['group_id1' => ['id' => 'group_id1', 'name' => 'group_name', ...], 'group_id2' => [ ... ]]
     */
    public function getGroupsList() {
        $request = new ZoomHttpRequest();
        $request
            ->setMethod(ZoomAPI::METHOD_GET)
            ->setUrl('/groups');

        $groups = [];

        $result = $this->sendRequest($request, true);
        if(isset($result['groups']) && is_array($result['groups'])) {
            foreach($result['groups'] as $group){
                $groups[$group['id']] = $group;
            }
        }
        return $groups;
    }


    /**
     * Delete member from Zoom group
     *
     * @param string $groupId
     * @param string $userId
     * @return bool|array - TRUE on success, array ['body' => array, 'headers' => array] on error
     */
    public function deleteGroupMember($groupId, $userId) {
        $request = new ZoomHttpRequest();
        $request
            ->setMethod(ZoomAPI::METHOD_DELETE)
            ->setUrl("/groups/{$groupId}/members/{$userId}");

        $result = $this->sendRequest($request, true);

        return $this->getLastHeaders()['HTTP_STATUS'] == 204 ? true : ['body' => $result, 'headers' => $this->getLastHeaders(), ];
    }


    /**
     * @return int
     */
    public function getDebugLevel() {
        return $this->debugLevel;
    }

    /**
     * @param int $debugLevel
     * @return self
     */
    public function setDebugLevel($debugLevel) {
        $this->debugLevel = $debugLevel;
        return $this;
    }

    /**
     * Log (human readable) something
     *
     * @param $debugMinLevel
     * @param $location
     * @param $data
     * @param bool $toStdOutput
     */
    public function log($debugMinLevel, $location, $data, $toStdOutput = false) {
        if($this->debugLevel >= $debugMinLevel) {
            @file_put_contents(
                APP_ROOT.'/logs/ZoomAPI_' . date('Y-m-d') . '.log',
                date('d.m.Y H:i:s') . ' [' . $location . ']: ' . $data . PHP_EOL,
                FILE_APPEND
            );

            if($toStdOutput) {
                echo date('d.m.Y H:i:s') . ' [' . $location . ']: ' . $data . PHP_EOL; flush();
            }
        }
    }

    /**
     * Translate array with int CURLOPT_* key constants to string keys (for logging purposes)
     *
     * @param array $options
     * @return array
     */
    public function translateCurlOptions($options) {
        $res = [];
        $knownOptions = [];

        foreach(['CURLOPT_HEADER', 'CURLOPT_RETURNTRANSFER', 'CURLOPT_SSL_VERIFYHOST', 'CURLOPT_SSL_VERIFYPEER', 'CURLOPT_CUSTOMREQUEST',
                 'CURLOPT_POST', 'CURLOPT_POSTFIELDS', 'CURLOPT_HTTPHEADER', 'CURLOPT_URL', 'CURLOPT_SAFE_UPLOAD', ] as $knownOption) {
            $knownOptions[constant($knownOption)] = $knownOption;
        }

        foreach($options as $optionId => $value){
            if(array_key_exists($optionId, $knownOptions)) {
                $res[$knownOptions[$optionId]] = $value;
            }
            else {
                $res['CURLOPT_ID_' . $optionId] = $value;
            }
        }

        return $res;
    }

    /**
     * Get last curl error array ['code' => int, 'message' => 'CURL Error text']
     *
     * @return array
     */
    public function getCurlLastError() {
        return $this->curlLastError;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @return string
     */
    public function getRedirectURI()
    {
        return $this->redirectURI;
    }

    /**
     * Return name of Zoom user type (Basic / Licensed)
     *
     * @param $typeId
     * @return string
     */
    public function translateZoomUserType($typeId) {
        return $typeId == self::USER_TYPE_BASIC
            ? 'Basic'
            : ($typeId == self::USER_TYPE_LICENSED
                ? 'Licensed'
                : ($typeId == self::USER_TYPE_ONPREM
                    ? "On-prem" : "Type {$typeId}"));
    }


    /**
     * Translate meeting type_id to string
     *
     * @param $typeId
     * @return string
     */
    public function translateMeetingType($typeId) {
        switch($typeId) {
            case 1:
                return 'Instant';
            case 2:
                return 'Scheduled';
            case 3:
                return 'Recurring with no fixed time';
            case 8:
                return 'Recurring with fixed time';
            default:
                return 'Type #'.$typeId;
                break;
        }
    }

}



