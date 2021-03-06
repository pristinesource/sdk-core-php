<?php

/*
 * Copyright 2016 MasterCard International.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of
 * conditions and the following disclaimer in the documentation and/or other materials
 * provided with the distribution.
 * Neither the name of the MasterCard International Incorporated nor the names of its
 * contributors may be used to endorse or promote products derived from this software
 * without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 *
 */

namespace MasterCard\Core;

use MasterCard\Core\Exception\ApiException;
use MasterCard\Core\Exception\InvalidRequestException;
use MasterCard\Core\Exception\AuthenticationException;
use MasterCard\Core\Exception\ObjectNotFoundException;
use MasterCard\Core\Exception\NotAllowedException;
use MasterCard\Core\Exception\SystemException;
use MasterCard\Core\ApiConfig;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ApiController {

    const HTTP_SUCCESS = 200;
    const HTTP_NO_CONTENT = 204;
    const HTTP_AMBIGUOUS = 300;
    const HTTP_REDIRECTED = 302;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_NOT_FOUND = 404;
    const HTTP_NOT_ALLOWED = 405;
    const HTTP_BAD_REQUEST = 400;

    protected $hostUrl = null;
    protected $client = null;
    protected $version = "NOT-SET";
    
    protected $logger = null;
    

    function __construct($version) {

          
        $this->logger = new Logger('ApiController');
        
        
        
        $this->checkState();
        
        if ($version != null) {
            $this->version = $version;
        }

        $fullUrl = ApiConfig::getLiveUrl();
        if (ApiConfig::isSandbox()) {
            $fullUrl = ApiConfig::getSandboxUrl();
        }

        if (filter_var($fullUrl, FILTER_VALIDATE_URL) == FALSE) {
            throw new ApiException("fullUrl: '" . $fullUrl . "' is not a valid url");
        }

        $this->hostUrl = $fullUrl;
        $this->hostUrl = Util::normalizeUrl($fullUrl);
        $this->client = new Client([
            'config' => [
                'curl' => [
                    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2
                ]
            ]
        ]);
    }

    /// <summary>
    /// Checks the state.
    /// </summary>
    private function checkState() {

        if (ApiConfig::getAuthentication() == null) {
            throw new ApiException("No ApiConfig::authentication has been configured");
        }

        if (filter_var(ApiConfig::getLiveUrl(), FILTER_VALIDATE_URL) == FALSE) {
            throw new ApiException("Invalid URL supplied for API_BASE_LIVE_URL");
        }


        if (filter_var(ApiConfig::getSandboxUrl(), FILTER_VALIDATE_URL) == FALSE) {
            throw new ApiException("Invalid URL supplied for API_BASE_SANDBOX_URL");
        }
    }

    private function removeForwardSlashFromTail($url) {
        return preg_replace('{/$}', '', $url);
    }

    private function appendToQueryString($s, $stringToAppend) {
        if (strpos($s, "?") == false) {
            $s .= "?";
        } else {
            $s .= "&";
        }

        $s .= $stringToAppend;

        return $s;
    }

    /**
     * @ignore
     */
    public function setClient($customClient) {
        $this->client = $customClient;
    }

    /**
     * @ignore
     */
    public function getUrl($operationConfig, $operationMetadata, &$inputMap) {

        $queryParams = array();
        
        $action = $operationConfig->getAction();
        $resourcePath = $operationConfig->getResourcePath();
        $queryList = $operationConfig->getQueryParams();
        $hostOverride = $operationMetadata->getHost();
        
        $url = "%s";
        
        $resolvedHostUrl = $this->hostUrl;
        if (!is_null($hostOverride)) {
            $resolvedHostUrl = $hostOverride;
        }
        
        $tmpUrl = Util::getReplacedPath($this->removeForwardSlashFromTail($resolvedHostUrl).$this->removeForwardSlashFromTail($resourcePath), $inputMap);
        array_push($queryParams, $tmpUrl);
        
        switch ($action) {
            case "read":
            case "delete":
            case "list":
            case "query":
                foreach ($inputMap as $key => $value) {
                    if(!is_array($value)) {
                        $url = $this->appendToQueryString($url, "%s=%s");
                        array_push($queryParams, Util::urlEncode(strval($key)));
                        array_push($queryParams, Util::urlEncode(strval($value)));
                    }
                }
                break;
            default:
                break;
        }
        
        // we need to remove any queryParameters specified in the inputMap and 
        // add them as quertParameters
        switch ($action) {
            case "create":
            case "update":
                $queryMap = Util::subMap($inputMap, $queryList);
                foreach ($queryMap as $key => $value) {
                    if(!is_array($value)) {
                        $url = $this->appendToQueryString($url, "%s=%s");
                        array_push($queryParams, Util::urlEncode(strval($key)));
                        array_push($queryParams, Util::urlEncode(strval($value)));
                    }
                }
                break;
            default:
                break;
        }

        $url = $this->appendToQueryString($url, "Format=JSON");
        $url = vsprintf($url, $queryParams);
        
        return $url;
    }

    public function getRequest($operationConfig, $operationMetadata, &$inputMap) {
        
        $action = $operationConfig->getAction();
        $resourcePath = $operationConfig->getResourcePath();
        $headerList = $operationConfig->getHeaderParams();
        $queryList = $operationConfig->getQueryParams();
        
        //arizzini: store seperately the header paramters
        $headerMap = Util::subMap($inputMap, $headerList);
        
        $url = $this->getUrl($operationConfig, $operationMetadata, $inputMap);
        
        $request = null;

        switch ($action) {
            case "create":
                $request = new Request("POST", $url, [], json_encode($inputMap));
                break;
            case "delete":
                $request = new Request("DELETE", $url);
                break;
            case "update":
                $request = new Request("PUT", $url, [], json_encode($inputMap));
                break;
            case "read":
            case "list":
            case "query":
                $request = new Request("GET", $url);
                break;
        }

        
        $request = $request->withHeader("Accept", "application/json");
        $request = $request->withHeader("Content-Type", "application/json");
        $request = $request->withHeader("User-Agent", "PHP-SDK/" . $this->version);
        foreach ($headerMap as $key => $value) {
            $request = $request->withHeader($key, $value);    
        }
        $request = ApiConfig::getAuthentication()->signRequest($url, $request);

        return $request;
    }

    public function execute($operationConfig, $operationMetadata, $inputMap) {
        $request = $this->getRequest($operationConfig, $operationMetadata, $inputMap);

        if (ApiConfig::isDebug()) {
            $this->logger->debug("---------------------");
            $this->logger->debug(">>request(".$request->getMethod().") ". $request->getUri()->__toString() );    
            $this->logger->debug(">>headers: ", $request->getHeaders());
            $this->logger->debug(">>body: ". $request->getBody());
        }

        try {
            $response = $this->client->send($request);
            $statusCode = $response->getStatusCode();
            $responseContent = $response->getBody()->getContents();

            
            if (ApiConfig::isDebug()) {
                $this->logger->debug("<<statusCode: ". $statusCode);
                $this->logger->debug("<<headers: ", $response->getHeaders());
                $this->logger->debug("<<body: ". $responseContent);
                $this->logger->debug("---------------------");
            }
            
            if ($statusCode < self::HTTP_AMBIGUOUS) {
                if (strlen($responseContent) > 0) {
                    return json_decode($responseContent, true);
                } else {
                    return array();
                }
            } else {
                $this->handleException($response, $request);
            }
        } catch (RequestException $ex) {
            if ($ex->hasResponse()) {
                $this->handleException($ex->getResponse(), $request);
            } else {
                throw new SystemException("An unexpected error has been raised: ".$ex->getMessage());
            }
        }
    }

    private function handleException($response, $request) {
        $status = $response->getStatusCode();
        $bodyContent = $response->getBody()->getContents();
        $bodyArray = json_decode($bodyContent, TRUE);

        //arizzini: in the case of an exception we always show the error.
        if (!ApiConfig::isDebug()) {
            $this->logger->debug("---------------------");
            $this->logger->debug(">>request(".$request->getMethod().") ". $request->getUri()->__toString() );    
            $this->logger->debug(">>headers: ", $request->getHeaders());
            $this->logger->debug(">>body: ". $request->getBody());
        }
        
        $this->logger->debug("<<statusCode: ". $response->getStatusCode());
        $this->logger->debug("<<headers: ", $response->getHeaders());
        $this->logger->debug("<<body: ". $bodyContent);
        $this->logger->debug("---------------------");

        if ($status < 500) {
            switch ($status) {
                case self::HTTP_BAD_REQUEST:
                    throw new InvalidRequestException("Bad request", $status, $bodyArray);
                    break;
                case self::HTTP_REDIRECTED:
                    throw new InvalidRequestException("Unexpected response code returned from the API, redirect is happening.", $status, $bodyArray);
                    break;
                case self::HTTP_UNAUTHORIZED:
                    throw new AuthenticationException("You are not authorized to make this request. Invalid request signing.", $status, $bodyArray);
                    break;
                case self::HTTP_NOT_FOUND:
                    throw new ObjectNotFoundException("Object not found", $status, $bodyArray);
                    break;
                case self::HTTP_NOT_ALLOWED:
                    throw new NotAllowedException("Operation not allowed", $status, $bodyArray);
                    break;
                default:
                    throw new InvalidRequestException("Bad request", $status, $bodyArray);
                    break;
            }
        } else {
            throw new SystemException("Internal Server Error:", $status, $bodyArray);
        }
    }

}
