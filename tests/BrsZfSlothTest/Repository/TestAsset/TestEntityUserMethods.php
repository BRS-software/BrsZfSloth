<?php

namespace BrsZfSlothTest\Repository\TestAsset;

class TestEntityUserMethods
{
    protected $id;
    protected $crtDate;
    protected $nick;
    protected $isActive;
    protected $comment;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCrtDate($date)
    {
        $this->crtDate = $date;
        return $this;
    }

    public function getCrtDate()
    {
        return $this->crtDate;
    }

    public function setNick($nick)
    {
        $this->nick = $nick;
        return $nick;
    }

    public function getNick()
    {
        return $this->nick;
    }

    public function setIsActive($flag)
    {
        $this->isActive = (bool) $flag;
        return $this;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }
}