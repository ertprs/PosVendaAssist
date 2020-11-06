<?php

namespace Mirrors;

class AbstractMirror
{
	protected $baseURI;
	protected $applicationKey;
	protected $applicationEnvironment;

	public function __construct()
	{
		global $_serverEnvironment;

		$this->baseURI = "https://api2.telecontrol.com.br";

		$this->applicationKey = strtoupper($_serverEnvironment) === "DEVELOPMENT"
			? "5691e568c713f13f1087ac5c0715ff1fb716b107" : "084f77e7ff357414d5fe4a25314886fa312b2cff";

		$this->applicationEnvironment = strtoupper($_serverEnvironment) === "DEVELOPMENT"
			? "HOMOLOGATION" : "PRODUCTION";
	}

	public function getApplicationKey()
	{
		return $this->applicationKey;
	}

	public function getApplicationEnvironment()
	{
		return $this->applicationEnvironment;
	}

  public function getBaseURI()
  {
  	return $this->baseURI;
  }

  public function setBaseURI($baseURI)
  {
  	$this->baseURI = $baseURI;
  }
}
