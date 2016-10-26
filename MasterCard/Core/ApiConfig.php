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

use MasterCard\Core\Security\AuthenticationInterface;

class ApiConfig {

    private static $SANDBOX = true;
    private static $DEBUG = false;
    private static $AUTHENTICATION = null;
    
    private static $API_BASE_LIVE_URL = "https://api.mastercard.com";
    private static $API_BASE_SANDBOX_URL = "https://sandbox.api.mastercard.com";

    
    /**
     * Sets the debug.
     * @param boolean $debug
     */
    public static function setDebug($debug)
    {
        static::$DEBUG = $debug;
    }
    
    
    /**
     * Sets get debug.
     */
    public static function isDebug()
    {
        return static::$DEBUG;
    }
    
    /**
     * Sets the sandbox.
     * @param boolean sandbox
     */
    public static function setSandbox($sandbox)
    {
        static::$SANDBOX = $sandbox;
    }
    
    
    /**
     * Sets get debug.
     */
    public static function isSandbox()
    {
        return static::$SANDBOX == true;
    }   
    
    /**
     * Sets get debug.
     */
    public static function isProduction()
    {
        return static::$SANDBOX == false;
    }


    /**
     * 
     * @return string
     */
    public static function getSandboxUrl() 
    {
        return self::$API_BASE_SANDBOX_URL;
    }
    
    /**
     * 
     * @return string
     */
    public static function getLiveUrl() 
    {
        return self::$API_BASE_LIVE_URL;
    }
    
    public static function setLocalhost() {
        self::$API_BASE_SANDBOX_URL = "http://localhost:8080";
        self::$API_BASE_LIVE_URL = "http://localhost:8080";
    }

        public static function unsetLocalhost() {
            self::$API_BASE_SANDBOX_URL = "https://sandbox.api.mastercard.com";
            self::$API_BASE_LIVE_URL = "https://api.mastercard.com";
        }

        public static function setAPIBaseCustomHosts($SandboxUrl = null, $LiveUrl = null) {
            self::$API_BASE_SANDBOX_URL = $SandboxUrl == null ? self::$API_BASE_SANDBOX_URL : $SandboxUrl;
            self::$API_BASE_LIVE_URL = $LiveUrl == null ? self::$API_BASE_LIVE_URL : $LiveUrl;
        }

        public static function unsetAPIBaseCustomHosts() {
            self::unsetLocalhost();
        }
    
    
    /**
     * Sets the sandbox.
     * @param boolean sandbox
     */
    public static function setAuthentication(AuthenticationInterface $authentication)
    {
        static::$AUTHENTICATION = $authentication;
    }
    
    
    /**
     * Sets get debug.
     */
    public static function getAuthentication()
    {
        return static::$AUTHENTICATION;
    }
    
}
