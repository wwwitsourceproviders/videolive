<?php

namespace Touhonoob\RateLimit;

/**
 * @author Peter Chung <touhonoob@gmail.com>
 * @date May 16, 2015
 */
class RateLimit
{

    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var int
     */
    public $maxRequests;

    /**
     *
     * @var int
     */
    public $period;

    /**
     *
     * @var int
     */
    public $ttl;

    /**
     *
     * @var Adapter
     */
    private $adapter;

    public function __construct($name, $maxRequests, $period, $adapter)
    {
        $this->name = $name;
        $this->maxRequests = $maxRequests;
        $this->period = $period;
        $this->ttl = $this->period;
        $this->adapter = $adapter;
    }

    /**
     * Rate Limiting
     * http://stackoverflow.com/a/668327/670662
     * @param string $id
     * @return boolean
     */
    public function check($id, $use = 1.0)
    {
        $rate = $this->maxRequests / $this->period;

        $t_key = $this->keyTime($id);
        $a_key = $this->keyAllow($id);

        if ($this->adapter->exists($t_key)) {
            $c_time = time();

            $time_passed = $c_time - $this->adapter->get($t_key);
            $this->adapter->set($t_key, $c_time, $this->ttl);

            $allow = $this->adapter->get($a_key);
            $allow += $time_passed * $rate;

            if ($allow > $this->maxRequests) {
                $allow = $this->maxRequests;
            }

            if ($allow < 1.0) {
                $this->adapter->set($a_key, $allow, $this->ttl);
                return 0;
            } else {
                $allow -= $use;
                $this->adapter->set($a_key, $allow, $this->ttl);
                return (int) ceil($allow);
            }
        } else {
            $allow = $this->maxRequests - $use;
            $this->adapter->set($t_key, time(), $this->ttl);
            $this->adapter->set($a_key, $allow, $this->ttl);
            return (int) ceil($allow);
        }
    }

    public function getAllow($id)
    {
        $this->check($id, 0.0);
        
        $a_key = $this->keyAllow($id);

        if (!$this->adapter->exists($a_key)) {
            return $this->maxRequests;
        } else {
            return max(0, floor($this->adapter->get($a_key)));
        }
    }

    /**
     * Purge rate limit record for $id
     * @param string $id
     */
    public function purge($id)
    {
        $this->adapter->del($this->keyTime($id));
        $this->adapter->del($this->keyAllow($id));
    }

    public function keyTime($id)
    {
        return $this->name . ":" . $id . ":time";
    }

    public function keyAllow($id)
    {
        return $this->name . ":" . $id . ":allow";
    }
}
