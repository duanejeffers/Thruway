<?php

namespace Thruway;


use Thruway\Authentication\AuthenticationManagerInterface;
use Thruway\Exception\InvalidRealmNameException;
use Thruway\Exception\RealmNotFoundException;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;

class RealmManager
{
    /**
     * @var array
     */
    private $realms;

    /**
     * @var ManagerInterface
     */
    private $manager;

    /**
     * @var bool
     */
    private $allowRealmAutocreate;

    /**
     * @var AuthenticationManagerInterface
     */
    private $defaultAuthenticationManager;

    function __construct(ManagerInterface $manager = null)
    {
        $this->realms = array();

        $this->manager = $manager;

        $this->allowRealmAutocreate = true;

        $this->defaultAuthenticationManager = null;
    }

    /**
     * @param string
     * @throws \Exception
     * @return Realm
     */
    public function getRealm($realmName)
    {
        if (!static::validRealmName($realmName)) {
            throw new InvalidRealmNameException;
        }

        if (!array_key_exists($realmName, $this->realms)) {
            if ($this->allowRealmAutocreate) {
                $this->manager->debug("Creating new realm \"" . $realmName . "\"");
                $this->realms[$realmName] = new Realm($realmName);
                $this->realms[$realmName]->setAuthenticationManager($this->getDefaultAuthenticationManager());
                $this->realms[$realmName]->setManager($this->manager);
            } else {
                throw new RealmNotFoundException();
            }
        }

        return $this->realms[$realmName];
    }

    public function addRealm(Realm $realm) {
        $realmName = $realm->getRealmName();

        if (!static::validRealmName($realm->getRealmName())) {
            throw new InvalidRealmNameException;
        }

        if (array_key_exists($realm->getRealmName(), $this->realms)) {
            throw new \Exception("There is already a realm \"" . $realm->getRealmName() . "\"");
        }

        $this->manager->debug("Adding realm \"" . $realmName . "\"");

        if ($realm->getManager() instanceof ManagerDummy) {
            /** remind people that we don't setup the manager for them if they
             * are creating their own realms */
            $this->manager->warning("Realm \"" . $realmName . "\" is using ManagerDummy");
        }

        $this->realms[$realm->getRealmName()] = $realm;
    }

    static public function validRealmName($name)
    {
        // check to see if this is a valid name
        // TODO maybe use similar checks to Autobahn|Py
        if (strlen($name) < 1) {
            return false;
        }
        //throw new \UnexpectedValueException("Realm name too short: " . $realmName);
        if ($name == "WAMP1") {
            return false;
        }
        //throw new \UnexpectedValueException("Realm name \"WAMP1\" is reserved.");

        return true;
    }

    /**
     * @return array
     */
    public function getRealms()
    {
        return $this->realms;
    }

    /**
     * @param boolean $allowRealmAutocreate
     */
    public function setAllowRealmAutocreate($allowRealmAutocreate)
    {
        $this->allowRealmAutocreate = $allowRealmAutocreate;
    }

    /**
     * @return boolean
     */
    public function getAllowRealmAutocreate()
    {
        return $this->allowRealmAutocreate;
    }

    /**
     * @param AuthenticationManagerInterface $defaultAuthenticationManager
     */
    public function setDefaultAuthenticationManager($defaultAuthenticationManager)
    {
        $this->defaultAuthenticationManager = $defaultAuthenticationManager;
    }

    /**
     * @return AuthenticationManagerInterface
     */
    public function getDefaultAuthenticationManager()
    {
        return $this->defaultAuthenticationManager;
    }



} 
