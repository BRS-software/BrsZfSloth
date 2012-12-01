<?php
namespace BrsZfSlothTest\Repository\TestAsset;

use BrsZfSloth\Repository\Repository;

class TestRepository extends Repository
{
    protected function testGetMethod($comment)
    {
        return [
            [
                'nick' => 'xxx',
                'comment' => $comment,
            ]
        ];
    }
}