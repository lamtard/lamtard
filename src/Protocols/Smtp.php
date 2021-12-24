<?php
namespace Protocols;

use \Workerman\Protocols\ProtocolInterface;
use \Workerman\Connection\ConnectionInterface;

class Smtp implements ProtocolInterface
{
    /**
     * Check the integrity of the package
     * If the packet length can be obtained, return the length of the packet in the buffer, otherwise return 0 to continue waiting for the data
     * If there is a problem with the protocol, you can return false, and the current client connection will be disconnected
     * @param string $buffer
     * @param ConnectionInterface $connection
     * @return int
     */
    public static function input($buffer, ConnectionInterface $connection)
    {
        // Get the position of the newline character "\n"
        $pos = strpos($buffer, "\n");
        // No line break, no way to know the packet length, return 0 to continue waiting for data
        if($pos === false)
        {
            return 0;
        }
        // There is a newline character, returns the current packet length (including the newline character)
        return $pos+1;
    }

    /**
     * Package, it will be called automatically when sending data to the client
     * @param string $buffer
     * @param ConnectionInterface $connection
     * @return string
     */
    public static function encode($buffer, ConnectionInterface $connection)
    {
        return $buffer."\r\n";
    }

    /**
     * Unpacking, when the number of data bytes received is equal to the value returned by input (value greater than 0), it will be called automatically
     * And pass to the $data parameter of the onMessage callback function
     * @param string $buffer
     * @param ConnectionInterface $connection
     * @return string
     */
    public static function decode($buffer, ConnectionInterface $connection)
    {
        // Remove the line breaks and restore to an array
        return trim($buffer);
    }
}
