<?php

/*
 *
 * Classe liée au transfert d'argent (de ryôs) entre les joueurs
 *
 */

class wanTransfert
{
	// instance du ninja demandant le transfert
	public $_ninja;
	
	// instance du ninja recevant le transfert
	public $_recepteur;
	
	// montant de la transaction
	public $montant;
	
	// message de redirection après éxécution
	public $advert;
	
	/*
	 * Constructeur de la classe
	 */
	public function __construct($_ninja)
	{
		if ($_ninja instanceof wanNinja)
			$this->_ninja = $_ninja;
		else
			return FALSE;
	}
	
	/*
	 * Définit le ninja receveur
	 */
	public function transfertSetRecepteur($id)
	{
		if ($id != $this->_ninja->ninja['ninja_id'])
		{
			$this->_recepteur = new wanNinja($id, TRUE);
			
			if (!$this->_recepteur)
			{
				$this->advert = "Ce ninja n'existe pas !";
				return FALSE;
			}
			else
			{
				return TRUE;
			}
		}
		else
		{
			$this->advert = "Tu ne peux pas te transferer des ryôs à toi-même !";
			return FALSE;
		}
	}
	
	/*
	 * Définit le montant de la transaction
	 */
	public function transfertSetMontant($montant)
	{
		if (!empty($montant))
		{
			$this->montant = (int) abs($montant);
			
			if ($this->transfertCheckMontant())
				return TRUE;
			else
				return FALSE;
		}
		else
		{
			$this->advert = "Le montant est incorrect";
			return FALSE;
		}
	}
	
	/*
	 * Contrôle la bourse du ninja envoyeur
	 */
	private function transfertCheckMontant()
	{
		if (!empty($this->montant))
		{
			if ($this->_ninja->ninjaCheckRyos($this->montant))
			{
				return TRUE;
			}
			else
			{
				$this->advert = "Tu n'as pas assez de ryôs pour transferer ".$this->montant." ryôs";
				return FALSE;
			}
		}
		else
		{
			$this->advert = "Tu n'as pas spécifié de montant";
			return FALSE;
		}
	}
	
	/*
	 * Effectue le transfert
	 */
	public function transfertExecute()
	{
		$this->_ninja->ninjaChangeRyos('pick', $this->montant);
		$this->_recepteur->ninjaChangeRyos('add', $this->montant);
		
		$log_data = array('sender_id' => $this->_ninja->ninja['ninja_id'],
						  'sender_login' => $this->_ninja->ninja['ninja_login'],
						  'ninja_id' => $this->_recepteur->ninja['ninja_id'],
						  'ninja_login' => $this->_recepteur->ninja['ninja_login'],
						  'mode' => 'send',
						  'amount' => $this->montant);

		$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

		wanLog::log($log);

		$log_data['mode'] = 'receipt';

		$log = array('ninja' => $this->_recepteur->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

		wanLog::log($log);

		$notification_data = array('sender_id' => $this->_ninja->ninja['ninja_id'],
								   'sender_login' => $this->_ninja->ninja['ninja_login'],
								   'amount' => $this->montant);

		wanNotify::notify($this->_recepteur->ninja['ninja_id'], __CLASS__, self::transfertExecuteNotificationParse($notification_data));
		
		$this->advert = $this->montant.' ryôs transférés à '.$this->_recepteur->ninja['ninja_login'];
		return TRUE;
	}

	/*
	 * LOGS PARSE
	 */
	public static function transfertExecuteLogParse($data)
	{
		switch ($data['mode'])
		{
			case 'send' :
				return '<a href="index.php?page=profil&id='.$data['sender_id'].'">'.$data['sender_login'].'</a> envoie '.$data['amount'].' ryôs 
						à <a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a>';
			break;

			case 'receipt' :
				return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> reçoit '.$data['amount'].' ryôs 
						de <a href="index.php?page=profil&id='.$data['sender_id'].'">'.$data['sender_login'].'</a>';
			break;
		}
	}

	/*
	 * NOTIFY PARSE
	 */
	public static function transfertExecuteNotificationParse($data)
	{
		return 'Tu as reçu '.$data['amount'].' ryôs de la part de <a href="index.php?page=profil&id='.$data['sender_id'].'">'.$data['sender_login'].'</a>';
	}
}