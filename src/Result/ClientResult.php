<?php

declare(strict_types=1);

namespace ApigilityConsumer\Result;

use ApigilityConsumer\Error\SpecialErrorMessage;
use RuntimeException;
use Zend\Json\Json;

/**
 * An 'entity' as value object that returned in \ApigilityConsumer\Service\ClientService::getClientResult().
 *
 */
class ClientResult implements ResultInterface
{
    
    /**
     * How objects should be encoded: as arrays or as stdClass.
     *
     * TYPE_ARRAY is array, which also conveniently evaluates to a boolean true
     * value, allowing it to be used with ext/json's functions.
     */
    public const TYPE_ARRAY  = 'ARRAY';
    public const TYPE_OBJECT = 'OBJECT';

    /** @var  bool */
    public $success;

    /** @var  array */
    public $data;

    /**
     * use static modifier on purpose, to make it usable when assign
     * ClientResult::$messages from outside (eg: on ApigilityConsumer\Service\ClientService::getClientResult()),
     * and it brought to all instance.
     *
     * @var array
     */
    public static $messages = [];

    // avoid class instantiation
    private function __construct()
    {
    }

    /**
     * Apply result with return its class as value object
     * when succeed, it will return static::fromSucceed()
     * when failure, it will return static::fromFailure().
     *
     * if decode failed, it will return static::fromFailure() with "Service unavailable" error message
     *
     * There is a condition when the STATIC $messages already setted up via ClientResult::$messages assignment
     * in \ApigilityConsumer\Service\ClientService::getClientResult(), then it will set 'validation_messages' and return failure.
     *
     * Otherwise, it will return succeed.
     *
     * @param string $result
     *
     * @return self
     */
    public static function applyResult(string $result, $objectDecodeType) : ResultInterface
    {
        if (!empty(self::$messages)) {
            $resultArray['validation_messages'] = self::$messages;

            return static::fromFailure($resultArray);
        }

        try {
            // for handle some characters like "\\" in string
            Json::$useBuiltinEncoderDecoder = true;
            // decode
            $resultBody = Json::decode($result, self::objectDecodeType($objectDecodeType));
        } catch (RuntimeException $e) {
            $resultArray['validation_messages'] = [
                'http' => [
                    SpecialErrorMessage::SERVICE_UNAVAILABLE['code'] => SpecialErrorMessage::SERVICE_UNAVAILABLE['reason'],
                ],
            ];

            return static::fromFailure($resultArray);
        }

        if (isset($resultArray['validation_messages'])) {
            return static::fromFailure($resultArray);
        }

        return static::fromSucceed($resultBody);
    }

    /**
     * A success result, with 'success' property = true
     *
     * @param array $result
     *
     * @return self
     */
    private static function fromSucceed($result) : self
    {
        $self = new self();
        $self->success = true;
        $self->data    = $result;

        return $self;
    }

    /**
     * A failure result process, return self with success = false and its validation_messages when exists.
     *
     * @param array $result
     *
     * @return self
     */
    private static function fromFailure(array $result) : self
    {
        $self = new self();
        $self->success = false;

        if (isset($result['validation_messages']['http'])) {
            $self::$messages = $result['validation_messages'];
        } else {
            $self::$messages = (isset($result['validation_messages']))
                ? ['validation_messages' => $result['validation_messages']]
                : [];
        }

        return $self;
    }
    
    /**
     * object decode type (array or object)
     * @param string $type
     * @return number
     */
    private static function objectDecodeType($type)
    {
        return ($type === self::TYPE_ARRAY) ? Json::TYPE_ARRAY : Json::TYPE_OBJECT;
    }
    
    
}
