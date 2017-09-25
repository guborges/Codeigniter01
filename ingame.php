<?php
Class ingame extends CI_Model {
//Testando github	
// Valida se o time tem a quantidade minima de titulares para jogar uma partida.
public function checkTeam($team){
	$t1data  = $this->db->query('SELECT * FROM `players` WHERE time_id = "'. $team . '" AND titular = "1"');
	$t2data  = $this->db->query('SELECT * FROM `players` WHERE time_id = "'. $team . '" AND reserva = "1"');
	
	/*
	$this->db->select("time_id, titular");
	$this->db->where("titular", 1);
	$query = $this->db->get("players");
	*/

	$i = 0;
	if($t1data->num_rows() > 9 && $t2data->num_rows() > 3){
		$result = true;
	}else{
		$result = false;
	}
	return $result;
}
// Conta quantos titulares tem no time
public function countTitular($team){
	$teamSelect  = $this->db->query('SELECT * FROM `players` WHERE time_id = "'. $team . '" AND titular = "1"');

	/*
	$this->db->get("time_id, titular");
	$this->db->where("time_id", $team);
	$this->db->where("titular", 1);
	$query = $this->db->get("players");

	$count = $query->num_rows();
	*/
	$i = $teamSelect->num_rows();
	return $i;
}

// Conta quantos reservas tem selecionado no time
public function countReserva($team){
	$teamSelect  = $this->db->query('SELECT * FROM `players` WHERE time_id = "'. $team . '" AND reserva = "1"');

	/*
	$selecionarTime = $this->db->get("time_id, reserva")->where("time_id", $team)->where("reserva", 1);
	$contagem = $selecionarTime->num_rows();
	*/

	$i = $teamSelect->num_rows();
	return $i;
}

// Inseri o time na fila de buscador de partidas
public function insertPlayRoom($id, $tier, $type ){
	$get = $this->db->query("SELECT * FROM `play_room` WHERE time_id = '".$id."' ");
	if($get->num_rows() <= 0){
	$data = array(
	'time_id' => $id,
	'tier' => $tier,
	'type_run' => $type
	);
	$this->db->insert('play_room', $data);
	$result = true;
	}else{
	$result = false;
	}
	return $result;
}
// Processador da fila de busca de partida
public function processarPlayRoom(){

	// TYPE RUN:
	/**
	0 => Jogar Amistoso;
	*/
	
	$i = 0;	$x = 0;	$y = 0;	$z = 0;	$b = 0;
	$am = $this->db->query("SELECT * FROM `play_room` WHERE type_run = '0' AND tier = '5' LIMIT 2");
	$am1 = $this->db->query("SELECT * FROM `play_room` WHERE type_run = '0' AND tier = '10' LIMIT 2");
	$am3 = $this->db->query("SELECT * FROM `play_room` WHERE type_run = '0' AND tier = '20' LIMIT 2");
	$am4 = $this->db->query("SELECT * FROM `play_room` WHERE type_run = '0' AND tier = '35' LIMIT 2");
	$am5 = $this->db->query("SELECT * FROM `play_room` WHERE type_run = '0' AND tier = '50' LIMIT 2");
	foreach($am->result_array() as $datAm){
		if($i==0){
		$it1 = $datAm['time_id'];
		$i++;
		}else{
		$it2 = $datAm['time_id'];
		$this->ingame->createPreMatch($it1, $it2);
		}
	}	
	foreach($am1->result_array() as $datAm){
		if($x==0){
		$xt1 = $datAm['time_id'];
		$x++;
		}else{
		$xt2 = $datAm['time_id'];
		$this->ingame->createPreMatch($xt1, $xt2);		
		}
	}
	foreach($am2->result_array() as $datAm){
		if($y==0){
		$yt1 = $datAm['time_id'];
		$y++;
		}else{
		$yt2 = $datAm['time_id'];
		$this->ingame->createPreMatch($yt1, $yt2);		
		}
	}
}
// Cria o saguão para iniciar uma partida
public function createPreMatch($t1, $t2){

	$teams = array($t1, $t2);

	$rand_team = array_rand($teams, 2);
	$data = array(
	'team_pos_left' => $teams[$rand_team[0]],
	'team_pos_right' => $teams[$rand_team[1]],
	'ready_left' => 0,
	'ready_right' => 0	
	);
	$timeIds = array($t1, $t2);
	$this->db->where('time_id', $timeIds);
    $this->db->delete('play_room');
	
	$this->db->insert('pre_match', $data);
}

// Inicia a partida
public function createGame($t1, $t2){
	$this->db->select("*");
	$this->db->from("teams");
	$this->db->where("id",$t1['id']);
	$data1 = $this->db->get();
}

// Coleta os jogadores e retorna a formação INATIVA.
public function getFormation($t1, $t2){
	$this->db->select("*");
	$this->db->from("teams");
	$this->db->where("id",$t1['id']);
	$data1 = $this->db->get();
	
	foreach($data1->result_array() as $t1data){
		$formationt1 = $t1data['formacao_atual'];
	}
	$this->db->select("*");
	$this->db->from("teams");
	$this->db->where("id",$t2['id']);
	$data2 = $this->db->get();
	
	foreach($data2->result_array() as $t2data){
		$formationt2 = $t2data['formacao_atual'];
	}
	
	$formations = array(
	't1' => $formationt1,
	't2' => $formationt2
	);
	return $formations;
	
}

// Calcula a chance de avançar quando estiver no ataque.
public function calcAtkChance($atk, $def){
	$compAtk = $this->ingame->getAtacantesComp($atk);
	$compDef = $this->ingame->getDefesaComp($def);
	$atkNumber = $compAtk['t1']['force'] - $compDef['t2']['desarme'];
	$chance = 0;
	if($atkNumber >= 0 && $atkNumber <= 10){
		$chance = rand(0,70);
	}
	if($atkNumber > 10 && $atkNumber <= 30){
		$chance = rand(20,75);
	}
	if($atkNumber > 30 && $atkNumber <= 50){
		$chance = rand(25,85);
	}
	if($atkNumber > 50){
		$chance = rand(35,100);
	}
	$data = array(
	'chance' => $chance,
	'drible' => $compAtk['t1']['drible'],
	'cabeceio' => $compAtk['t1']['cabeceio'],
	'force' => $compAtk['t1']['force']
	);
	return $data;
}

// Calcula a chance de avançar quando estiver no Meio do campo
public function calcMeiaOfensivo($atk, $def){
	$meia = $this->ingame->getMeiaComp($atk, $def);
	$atkNumber = $meia['t1']['force'] - $meia['t2']['desarme'];
	$chance = 0;
	if($atkNumber >= 0 && $atkNumber <= 10){
		$chance = rand(0,70);
	}
	if($atkNumber > 10 && $atkNumber <= 30){
		$chance = rand(20,75);
	}
	if($atkNumber > 30 && $atkNumber <= 50){
		$chance = rand(25,85);
	}
	if($atkNumber > 50){
		$chance = rand(35,100);
	}
	return $chance;
}

// Calcula a chance de avançar quando estiver nas laterais
public function calcLateralOfensivo($atk, $def){
	$lat = $this->ingame->getLateralComp($atk, $def);
	$atkNumber = $lat['t1']['force'] - $lat['t2']['desarme'];
	$chance = 0;
	if($atkNumber >= 0 && $atkNumber <= 10){
		$chance = rand(0,70);
	}
	if($atkNumber > 10 && $atkNumber <= 30){
		$chance = rand(20,75);
	}
	if($atkNumber > 30 && $atkNumber <= 50){
		$chance = rand(25,85);
	}
	if($atkNumber > 50){
		$chance = rand(35,100);
	}
	return $chance;
}
// Construi uma Array com dados dos Atacantes para comparar com a Defesa de um dos times em partida.
public function getAtacantesComp($t1){
	$t1data  = $this->db->query('SELECT controle_de_bola, passe_de_bola, forca_do_chute, finalizacao, drible, level, cabeceio FROM `players` WHERE time_id = "'. $t1 . '" AND posicao = "0" AND titular = "1"');
	$compArray = array();
	$i = 0;
	$x = 0;
	$compArray['t1']['drible'] = 0;
	$compArray['t1']['cabeceio'] = 0;
	$compArray['t1']['force'] = 0;
	$compArray['t1']['points'] = 0;
	foreach($t1data->result_array() as $atkMember){
		$id = $atkMember['id'];
		$dataEnergy = $atkMember['energia'];
		if($dataEnergy == 100){
		$dataEnergy = $dataEnergy*1.1;	
		}		
			$atkControlBall 		= 			$atkMember['controle_de_bola'];
			$atkPassBall 			= 	       	   $atkMember['passe_de_bola'];
			$atkKickingForce    	=         	  $atkMember['forca_do_chute'];
			$atkFinalizacao	    	=         	     $atkMember['finalizacao'];
			$atkDrible          	=                 	  $atkMember['drible'];
			$atkLevel           	=                  	   $atkMember['level'];
			$atkCabeceio  			=         	 	  	$atkMember['cabeceio'];
		
		$atkResume = ( $atkLevel*5 + ( $atkControlBall + $atkPassBall + $atkKickingForce + ( $atkDrible * 1.5 ))) * $dataEnergy;
		
		$compArray['force'] += $atkControlBall+$atkFinalizacao+$atkKickingForce;
		$compArray['cabeceio'] += $atkCabeceio;
		$compArray['drible'] += $atkDrible;
		$compArray['points'] += $atkResume;
	}
	return $compArray;	
}
// Construi uma Array com dados da Defesa para calcular os Atacantes de um dos times em partida.
public function getDefesaComp($t2){
	$t2data  = $this->db->query('SELECT * FROM `players` WHERE time_id = "'. $t2 . '" AND posicao = "3" AND titular = "1"');
	$compArray = array();
	$x = 0;
	$compArray['t2']['desarme'] = 0;
	$compArray['t2']['points'] = 0;
	foreach($t2data->result_array() as $defMember){
		$id = $defMember['id'];
		$dataEnergy = $defMember['energia'];
		if($dataEnergy == 100){
		$dataEnergy = $dataEnergy*1.1;	
		}		
			$defControlBall 		= 			$defMember['controle_de_bola'];
			$defDesarme				= 	       	   		 $defMember['desarme'];
			$defBloqueio    		=         	  		$defMember['bloqueio'];
			$defMarcacao         	=                 	$defMember['marcacao'];
			$defLevel           	=                  	   $defMember['level'];
		
		$defResume = ( $defLevel*5 + ( $defControlBall + $defDesarme + $defBloqueio + ( $defMarcacao * 1.5 ))) * $dataEnergy;
		
		$compArray['t2']['desarme'] += $defDesarme+$defMarcacao+$defBloqueio;
		
		$compArray['t2']['points'] += $defResume;
		$x++;
	}
	return $compArray;
}
// Coleta informações da composição da partida no Meio de campo.
public function getMeiaComp($t1, $t2){
	$t1data  = $this->db->query('SELECT * FROM `players` WHERE time_id = "'. $t1 . '" AND posicao = "1" AND titular = "1"');
	$t2data  = $this->db->query('SELECT * FROM `players` WHERE time_id = "'. $t2 . '" AND posicao = "1" AND titular = "1"');
	$compArray = array();
	$i = 0;
	$x = 0;
	$compArray['t1']['desarme'] = 0;
	$compArray['t1']['force'] = 0;
	$compArray['t2']['desarme'] = 0;
	$compArray['t2']['force'] = 0;
	foreach($t1data->result_array() as $meiMember){
		$id = $meiMember['id'];
		$dataEnergy = $meiMember['energia'];
		if($dataEnergy == 100){
		$dataEnergy = $dataEnergy*1.1;	
		}		
			//ofensivo
			$meiControlBall 		= 			$meiMember['controle_de_bola'];
			$meiPassBall			= 	       	   $meiMember['passe_de_bola'];
			$meiKickingForce   		=         	  $meiMember['forca_do_chute'];
			$meiDrible   			=         	 	  	  $meiMember['drible'];
			
			//defensivo
			$meiMarcacao         	=                 	$meiMember['marcacao'];
			$meiDesarme				= 	       	   		 $meiMember['desarme'];
			$meiLevel           	=                  	   $meiMember['level'];
		
		$meiResume = ( $meiLevel + ( $meiControlBall + $meiBloqueio + ( $meiMarcacao * 1.5 ))) * $dataEnergy;
		
		$compArray['t1']['desarme'] += $meiDesarme+$meiMarcacao;
		$compArray['t1']['cabeceio'] += $meiCabeceio;
		$compArray['t1']['force'] += $meiControlBall+$meiPassBall+$meiKickingForce+$meiDrible;
		
		$compArray['t1'][$i]['points'] = $meiResume;
		$compArray['t1'][$i]['id'] = $id;
		$compArray['t1'][$i]['name'] = $meiMember['name'];		
		$i++;
	}
	foreach($t2data->result_array() as $meiMember){
		$id = $meiMember['id'];
		$dataEnergy = $meiMember['energia'];
		if($dataEnergy == 100){
		$dataEnergy = $dataEnergy*1.1;	
		}		
			//ofensivo
			$meiControlBall 		= 			$meiMember['controle_de_bola'];
			$meiPassBall			= 	       	   $meiMember['passe_de_bola'];
			$meiKickingForce   		=         	  $meiMember['forca_do_chute'];
			$meiDrible   			=         	 	  	  $meiMember['drible'];
			
			//defensivo
			$meiMarcacao         	=                 	$meiMember['marcacao'];
			$meiDesarme				= 	       	   		 $meiMember['desarme'];
			
			$meiLevel           	=                  	   $meiMember['level'];
		
		$meiResume = ( $meiLevel + ( $meiControlBall + $meiBloqueio + ( $meiMarcacao * 1.5 ))) * $dataEnergy;
		
		$compArray['t2']['desarme'] += $meiDesarme+$meiMarcacao;
		$compArray['t2']['force'] += $meiPassBall+$meiKickingForce+$meiDrible;
		
		$compArray['t2'][$x]['points'] = $meiResume;
		$compArray['t2'][$x]['id'] = $id;
		$compArray['t2'][$x]['name'] = $meiMember['name'];		
		$x++;
	}
	return $compArray;
}
// Coleta informações da composição da partida na Lateral.
public function getLateralComp($t1, $t2){
	$t1data  = $this->db->query('SELECT * FROM `players` WHERE time_id = "'. $t1 . '" AND posicao = "2" AND titular = "1"');
	$t2data  = $this->db->query('SELECT * FROM `players` WHERE time_id = "'. $t2 . '" AND posicao = "2" AND titular = "1"');
	$compArray = array();
	$i = 0;
	$x = 0;
	$compArray['t1']['desarme'] = 0;
	$compArray['t1']['force'] = 0;
	$compArray['t2']['force'] = 0;
	$compArray['t2']['desarme'] = 0;
	$compArray['t1']['cruzamento'] = 0;
	$compArray['t2']['cruzamento'] = 0;
	foreach($t1data->result_array() as $latMember){
		$id = $latMember['id'];
		$dataEnergy = $latMember['energia'];
		if($dataEnergy == 100){
		$dataEnergy = $dataEnergy*1.1;	
		}		
			//ofensivo
			$latControlBall 		= 			$latMember['controle_de_bola'];
			$latPassBall			= 	       	   $latMember['passe_de_bola'];
			$latDrible   			=         	 	  	  $latMember['drible'];
			$latCruzamento 			=         	 	  $latMember['cruzamento'];
			
			//defensivo
			$latMarcacao         	=                 	$latMember['marcacao'];
			$latDesarme				= 	       	   		 $latMember['desarme'];
			
			$latLevel           	=                  	   $latMember['level'];
		
		$latResume = ( $latLevel + ( $latControlBall + $latBloqueio + ( $latMarcacao * 1.5 ))) * $dataEnergy;
		
		$compArray['t1']['desarme'] += $latControlBall+$latDesarme+$latMarcacao;
		$compArray['t1']['force'] += $latControlBall+$latPassBall+$latDrible;
		$compArray['t1']['cruzamento'] += $latCruzamento;
		
		$compArray['t1'][$i]['points'] = $latResume;
		$compArray['t1'][$i]['id'] = $id;
		$compArray['t1'][$i]['name'] = $latMember['name'];		
		$i++;
	}
	foreach($t2data->result_array() as $latMember){
		$id = $latMember['id'];
		$dataEnergy = $latMember['energia'];
		if($dataEnergy == 100){
		$dataEnergy = $dataEnergy*1.1;	
		}		
			//ofensivo
			$latControlBall 		= 			$latMember['controle_de_bola'];
			$latPassBall			= 	       	   $latMember['passe_de_bola'];
			$latKickingForce   		=         	  $latMember['forca_do_chute'];
			$latDrible   			=         	 	  	  $latMember['drible'];
			
			//defensivo
			$latMarcacao         	=                 	$latMember['marcacao'];
			$latDesarme				= 	       	   		 $latMember['desarme'];
			
			$latLevel           	=                  	   $latMember['level'];
		
		$latResume = ( $latLevel + ( $latControlBall + $latBloqueio + ( $latMarcacao * 1.5 ))) * $dataEnergy;
		
		$compArray['t2']['desarme'] += $latControlBall+$latDesarme+$latMarcacao;
		$compArray['t2']['force'] += $latPassBall+$latDrible+$latCruzamento;
		$compArray['t2']['force'] += $latControlBall+$latPassBall+$latDrible;
		$compArray['t2']['cruzamento'] += $latCruzamento;
		
		$compArray['t2'][$x]['points'] = $latResume;
		$compArray['t2'][$x]['id'] = $id;
		$compArray['t2'][$x]['name'] = $latMember['name'];		
		$x++;
	}
	return $compArray;
}
// Calcula chance do gol no cabeceio.
public function getGolCabeceio($team,$def){
	$t1data  = $this->db->query('SELECT * FROM `players` WHERE time_id = "'. $team . '" AND posicao = "0" AND titular = "1"');
	$t2data  = $this->db->query('SELECT * FROM `players` WHERE time_id = "'. $def . '" AND posicao = "3" AND titular = "1"');
	$t2goleiro  = $this->db->query('SELECT * FROM `players` WHERE time_id = "'. $def . '" AND posicao = "4" AND titular = "1" LIMIT(1)');
	$compArray = array();
	$compArray['t1']['cabeceio'] = 0;
	$compArray['t1']['force'] = 0;
	$compArray['t2']['cabeceio'] = 0;
	foreach($t1data->result_array() as $atkMember){
		$id = $atkMember['id'];
		$dataEnergy = $atkMember['energia'];
		if($dataEnergy == 100){
		$dataEnergy = $dataEnergy*1.1;	
		}		
			$atkControlBall 		= 			$atkMember['controle_de_bola'];
			$atkPassBall 			= 	       	   $atkMember['passe_de_bola'];
			$atkKickingForce    	=         	  $atkMember['forca_do_chute'];
			$atkFinalizacao	    	=         	     $atkMember['finalizacao'];
			$atkDrible          	=                 	  $atkMember['drible'];
			$atkLevel           	=                  	   $atkMember['level'];
			$atkCabeceio  			=         	 	  	$atkMember['cabeceio'];
		
		$atkResume = ( $atkLevel + ( $atkControlBall + ($atkCabeceio*2.3) + $atkKickingForce)) * $dataEnergy;
		
		$compArray['t1']['force'] += $atkControlBall+$atkFinalizacao+$atkKickingForce;
		$compArray['t1']['cabeceio'] += $atkResume;
	}
	foreach($t2data->result_array() as $defMember){
		$id = $defMember['id'];
		$dataEnergy = $defMember['energia'];
		if($dataEnergy == 100){
		$dataEnergy = $dataEnergy*1.1;	
		}		
			$defControlBall 		= 			$defMember['controle_de_bola'];
			$defPassBall 			= 	       	   $defMember['passe_de_bola'];
			$defKickingForce    	=         	  $defMember['forca_do_chute'];
			$defFinalizacao	    	=         	     $defMember['finalizacao'];
			$defDrible          	=                 	  $defMember['drible'];
			$defLevel           	=                  	   $defMember['level'];
			$defDesarme  			=         	 	  	 $defMember['desarme'];
			$defCabeceio  			=         	 	  	$defMember['cabeceio'];
		
		$defResume = ( $defLevel + ( $defControlBall + ($defCabeceio*2.3) + $defDesarme)) * $dataEnergy;
		
		$compArray['t2']['force'] += $defControlBall+$defCabeceio+$defDesarme;
		$compArray['t2']['desarme'] += $defDesarme;
		$compArray['t2']['cabeceio'] += $defResume;
	}
	foreach($t2goleiro->result_array() as $goleiro){
		$goleiroDef = $goleiro['bloqueio'];
	}
	$result = false;
	$ataque = $compArray['t1']['force']*2;
	if($ataque > $compArray['t2']['force']){
		if($compArray['t1']['cabeceio'] > $compArray['t2']['cabeceio']){
			$randMin = $goleiroDef+$compArray['t2']['desarme'];
			$randMax = ($goleiroDef+$compArray['t2']['desarme'])*3;
			$perc = ($randMin/$randMax)*100;
			if($perc > 50){
				$result = true;
			}			
		}
	}
	return $result;	
}
// Função raiz para analisar a chance do gol ser efetivo
public function chanceGol($atk, $def, $type){
	$result = false;
	if(strpos($type, 'cruzamento')!==false){
		$getGol = $this->ingame->getGolCabeceio($atk, $def);
		if($getGol !== false){
		$result = true;		
	}
	return $result;
}
}
public function chanceFalta($data){
	
}
// Função que inicia a partida.
public function leftStart($t1,$t2){
	$meiaAtk = $this->ingame->calcMeiaOfensivo($t1,$t2);
	$falta = rand(0,10);
	if($meiaAtk > 50){
		$falta = rand(2,15);
		$passeLateral = rand(0,100);
		$passeAtaque = rand(0,100);
		if($passeLateral > 50 && $passeAtaque <= 50){
			$latRes = $this->ingame->calcLateralOfensivo($t1,$t2);
			if($latRes > 50){
			$calcGol = $this->ingame->chanceGold($t1,$t2,'cruzamento');
			if($calcGol !== false){				
			$this->ingame->registraGol($t1, $t2);
			$this->ingame->runningGame($t2,$t1);
			}
			}
			}
		else if($passeLateral < 50 && $passeAtaque > 50){
			$atkRes = $this->ingame->calcAtkChance($t1,$t2);
			if($atkRes > 50){						
			$this->ingame->registraGol($t1, $t2);
			$this->ingame->runningGame($t2,$t1);
			}else{
			$this->ingame->runningGame($t2,$t1);			
			}
		}
	}else{
	$this->ingame->runningGame($t2,$t1);		
	}
	return false;
}
// Função raiz que inicia os lances quando a posse de bola é invertida
public function runningGame($t1,$t2,$gId){
	$meiaAtk = $this->ingame->calcMeiaOfensivo($t1,$t2);
	$getMarcacao = $this->ingame->getMarcacao($t2);
	$falta = (rand(0,10)*$getMarcacao)+2;
	if($meiaAtk > 50){
	$falta = (rand(5,12)*$getMarcacao)+2;
		$passeLateral = rand(0,100);
		$passeAtaque = rand(0,100);
		if($falta > 12){
			$cartao = $this->ingame->getFaltaValues($t2,$gId);
			
		}
		if($passeLateral > 50 && $passeAtaque <= 50){
			$latRes = $this->ingame->calcLateralOfensivo($t1,$t2);
			if($latRes > 50){
			$calcGol = $this->ingame->chanceGold($t1,$t2,'cruzamento');
			if($calcGol !== false){				
			$this->ingame->registraGol($t1, $t2);
			$this->ingame->runningGame($t2,$t1);
			}
			}
		}
		else if($passeLateral < 50 && $passeAtaque > 50){
			$atkRes = $this->ingame->calcAtkChance($t1,$t2);
			if($atkRes > 50){						
			$this->ingame->registraGol($t1, $t2);
			$this->ingame->runningGame($t2,$t1);
			}else{
			$this->ingame->runningGame($t2,$t1);			
			}
		}
	}else{
	$this->ingame->runningGame($t2,$t1);		
	}
	return false;
}
public function ballPosition($left,$right){
	
}

// Calcula a gravidade da falta de acordo com a marcação.
public function getFaltaValues($team){
		// Card time:
		// 1 => Amarelo
		// 2 => Vermelho
		// ex: $card = $time.",0";
		// onde 1 => Amarelo.
		
		$t1data  = $this->db->query('SELECT * FROM `players` WHERE time_id = "'. $team . '" AND posicao != "4" AND titular = "1"');
		$elemento = array();
		$pArray = array();
		$cards = array();
		foreach($t1data->result_array() as $player){
			$id = $player['id'];
			$name = $player['name'];
			$cards = "(".$player['recent_card'].")";
			array_push($elemento, $id);
			$qArray = array(
			$id => $name
			);
			$cArray = array(
			$id => $cards
			);
			array_push($pArray, $qArray);
			array_push($cards, $cArray);			
		}
		$jogador = array_rand($elemento, 1);
		
		$nivel = $this->ingame->getMarcacao($team);
		$playerCard = explode(',', $cards[$jogador]);
		$j = '('.$jogador.'.';
		$leave = false;
		$cn = 0;
		foreach($playerCard as $pc){
			if($pc > 0 && $cn = 0){
				$leave = true;
			}
		}
		if($nivel >= 0){
		$cartaoAmarelo = rand(0,45);
		}
		if($nivel >= 1){
		$cartaoAmarelo = rand(15,70);
		$cartaoVermelho = rand(0,95);	
		}
		if($nivel == 2){
		$cartaoVermelho = rand(60,100);		
		}
		if($cartaoAmarelo > 40){
			if($leave == true){
				$time = $this->ingame->getTimeInGame($gId);
				$card = $time.",1";
				$this->ingame->recentCardUpdate($jogador, $card);
				$this->ingame->quickOutPlayer($jogador);
			}else{
				recentCardUpdate($jogador, $card);
				$msg = "O jogador ".$pArray[$jogador]." levou um cartão amarelo.";
			}
		}
		if($cartaoVermelho > 90){
			if($leave == true){
				$time = $this->ingame->getTimeInGame($gId);
				$card = $time.",2";
				$this->ingame->recentCardUpdate($jogador, $card);
				$this->ingame->quickOutPlayer($jogador);
				$msg = "O jogador ".$pArray[$jogador]." levou um cartão vermelho.";
			}
		}
		return $msg;
}
// Atualiza o historico de cartão do jogador $id = jogador
public function recentCardUpdate($id, $card){
	$this->db->set('recent_card', $card);
	$this->db->where('id', $id);
	$this->db->update('players');
}
public function quickOutPlayer($p){
	$this->db->set('titular', '0');
	$this->db->where('id', $id);
	$this->db->update('players');	
}
public function updateTimeInGame($gId,$time){
	
}
public function getTimeInGame($gId){
	
}
public function getMarcacao($team){

	// Marcação
	// 0 => equilibrado
	// 1 => forçar
	// 2 => muito forte

	$t1data  = $this->db->query('SELECT * FROM `team` WHERE id = "'. $team. '"');
	foreach($t1data->result_array() as $result){
		$marcacao = $result['marcacao'];
	}
	return $marcacao;
}
}