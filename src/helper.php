<?php

use Xiaoe\Task\Server;

/**
 * @return \Psr\Log\AbstractLogger
 */
function logger()
{
    return Server::getLogger();
}


