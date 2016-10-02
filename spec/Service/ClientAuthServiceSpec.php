<?php

namespace ApigilityConsumer\Spec\Service;

use ApigilityConsumer\Result\ClientAuthResult;
use ApigilityConsumer\Service\ClientAuthService;
use Kahlan\Plugin\Double;
use Zend\Http\Client;
use Zend\Http\Response;
use Zend\Json\Json;

describe('ClientAuthService', function () {
    
    beforeAll(function () {
        $this->client = Double::instance(['extends' => Client::class]);
        $this->service = new ClientAuthService(
            'http://api.host.url',
            $this->client,
            [
                'grant_type'    => 'password',
                'client_id'     => 'foo',
                'client_secret' => 'foo_s3cret',
            ]
        );
    });
    
    describe('->callAPI', function () {
        it('define grant_type = client_credentials will not set username form-data', function () {
            $client = Double::instance(['extends' => Client::class]);
            $service = new ClientAuthService(
                'http://api.host.url',
                $client,
                [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => 'foo',
                    'client_secret' => 'foo_s3cret',
                ]
            );
            
            $data = [
                'api-route-segment' => '/oauth',
                'form-request-method' => 'POST',
            ];
            
            allow($client)->toReceive('setRawBody')->with(Json::encode(
                [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => 'foo',
                    'client_secret' => 'foo_s3cret',
                ]
            ));
            
            $result = $service->callAPI($data);
            expect($result)->toBeAnInstanceOf(ClientAuthResult::class);
        });
        
        it('return "ClientAuthResult" instance', function () {
            $data = [
                'api-route-segment' => '/oauth',
                'form-request-method' => 'POST',
                
                'form-data' => [
                    'username'    => 'foo',
                    'password'     => 'foo',
                ],
            ];
            
            allow($this->client)->toReceive('setRawBody')->with(Json::encode($data['form-data']));
            
            $result = $this->service->callAPI($data);
            expect($result)->toBeAnInstanceOf(ClientAuthResult::class);
        });
        
        it('define timeout parameter will set timeout for http call', function () {
            $data = [
                'api-route-segment' => '/oauth',
                'form-request-method' => 'POST',
                
                'form-data' => [
                    'username'    => 'foo',
                    'password'     => 'foo',
                ],
            ];
            
            allow($this->client)->toReceive('setOptions')->with(['timeout' => 100]);
            
            $result = $this->service->callAPI($data, 100);
            expect($result)->toBeAnInstanceOf(ClientAuthResult::class);
        });
        
        it('return invalid request when invalid data provided', function() {
            $data = [
                'api-route-segment' => '/oauth',
                'form-request-method' => 'POST',
                
                'form-data' => [
                    'username'    => 'foo',
                    'password'     => 'foo',
                ],
            ];
            
            $response = Double::instance(['extends' => Response::class]);
            allow($response)->toReceive('getStatusCode')->andReturn(400);
            allow($response)->toReceive('getBody')->andReturn(<<<json
{
  "type": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
  "title": "invalid_client",
  "status": 400,
  "detail": "The client credentials are invalid"
}
json
            );
            
            allow($this->client)->toReceive('send')->andReturn($response);
            
            $result = $this->service->callAPI($data);
            expect($result)->toBeAnInstanceOf(ClientAuthResult::class);
            expect($result->success)->toBe(false);
        });
    });
    
});