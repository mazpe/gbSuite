<?php
/*
 * Created on 16/05/2008
 *
 * Este script se encarga de crear la clase que sirve 
 * para crear tablas de catalogos sencillos.
 * esto para generalizar la creacion de catalogos de manera facil
 *  
 */
 
 class Catalogo
 {
 	var $consulta;
 	var $campos;
 	var $conexion;
 	var $resultado;
 	var $clase;
 	var $atributos;
 	var $titulo;
 	var $links;
 	var $formatos; //este es para poner una funcion para darle formato a los datos.
 	var $atributosCampos;
 	var $columnas; //estas son las columnas extras que se necesiten;
 	var $formulario; 
 	
 	var $prefix; 		//esto es lo que se escribe antes de la tabla del catalogo
 	var $sufix;			//esto es lo que se escribe antes de la tabla del catalogo
 	var $totales;
 	
 	var $close; //esto se si se quiere se desea cerrar la tabla y los otros componentes.
 	
 	var $rowAtts;
 	
 	var $divClass; // esta es la clase del div que contiene a todo el catalogo
 	var $innerHtml;
 	
 	var $rowLink;
 	
 	var $formatoTotales;
 	
 	function Catalogo($consulta,$campos,$conexion)
 	{
 		$this->conexion = $conexion;
 		$this->consulta = $consulta;
 		$this->campos = $campos;
 		$this->atributosCampos = array();
 		$this->columnas = array();
 		$this->clase = "app-report-table";
 		$this->totales = array();
 		$this->close = true;
 		
 		$this->divClass = "";
 		
 		$this->formatoTotales = array();
 	}

	function formatDolar($value, $extra)
	{
		return "$".number_format($value, 0);
	}
 	 	
 	function setPrefix($valor)
 	{
 		$this->prefix = $valor;
 	}
 	function setSufix($valor)
 	{
 		$this->sufix = $valor;
 	}
 	 	
 	function setTitulo($titulo)
 	{
 		$this->titulo = $titulo;
 	}
 	
 	
 	function crearEdicion($catalogo,$id1,$id2)
 	{
 		$this->agregarColumna(1,"","<a href='/Nicautor/catalogos/modificar.php?catalogo=$catalogo&id1=[$id1]". ($id2 ? "&id2=[$id2]" :"" ) ."'><img src='/Nicautor/imagenes/editar.gif'></a>" .
  								 "<a href='/Nicautor/catalogos/eliminar.php?catalogo=$catalogo&id1=[$id1]". ($id2 ? "&id2=[$id2]" :"" ) ."'><img src='/Nicautor/imagenes/delete.gif'></a>" 
  								 ," width=40px align=center% ");	
 	}
 	
 	/*este es un arreglo de tipo campo=>inicializador -- 0*/ 
 	function agregarTotales($campos)
 	{
 		$this->totales = $campos;
 	}
 	
 	function agregarAtributoCampos($campos,$attr)
 	{
 		foreach($campos as $campo)
 		{
 			
 			$this->agregarAtributoCampo($campo,$attr);
 		}
 	}
 	
 	function agregarFormato($campo,$funcion,$extraParams)
 	{
 		//$this->formatos[$campo] = true;
 		$this->formatos[$campo]['funcion'] = $funcion;
 		$this->formatos[$campo]['params'] = $extraParams;
 	}
 	
 	/*
 	 * Este metodo lo que hace es que agrega un atributo para las columnas de los campos
 	 */
 	function agregarAtributoCampo($campo,$atributo)
 	{
 		$this->atributosCampos[$campo] = $atributo;
 	}
 	/*
 	 * Este metodo lo que hace es que agrega un atributos para las columnas de los campos
 	 */
 	function agregarAtributosCampos($valores)
 	{
 		$this->atributosCampos = array_merge($this->atributosCampos,$valores);
 	}
 	
 	
 	function setRowAttributes($row)
 	{
 		$this->rowAtts = $row; 
 	}
 	
 	function createForm($name,$action,$input,$method)
 	{
 		$this->formulario['name'] = $name;
 		$this->formulario['action'] = $action;
 		$this->formulario['input'] = $input;
 		$this->formulario['method'] = $method;
 	}
 		
 	
 	function agregarColumna($posicion,$nombre,$valor,$atributos)
 	{
 		$this->columnas[$posicion]['nombre'] = $nombre;
 		$this->columnas[$posicion]['valor'] = $valor; 
 		$this->columnas[$posicion]['atributos'] = $atributos;
 	}
 	
 	
 	/*
 	 * esta funcion agrega un link a un campo especifico.
 	 * se le tiene que mandar la cadena de link con el formato adecuado para 
 	 * que se cree el link correctamente.
 	 * este formato es asi:
 	 * 	'/Nicautor/pagina.php?var1={campo1}&var2={campo2}'
 	 */
 	function crearLink($campo,$link)
 	{
 		$this->links[$campo] = $link;
 	}
 	
 	
 	function setCss($clase)
 	{
 		$this->clase = "catalogo";//$clase;
 	}
 	
 	function setTableAtt($att)
 	{
 		$this->atributos = $att;
 	}
 	
 	function setDivClass($class)
 	{
 		$this->divClass = $class;
 	} 
 	 	
 	function toString()
 	{
 		
 		echo $this->prefix;
 		
 		$this->resultado = $this->conexion->exec_query($this->consulta);
 		
 		echo "<div width=100% ". ($this->divClass == "" ? "" : "class=".$this->divClass ) .">";
 		
 		echo $this->innerHtml;
 		
 		if(isset($this->formulario['name']))
 			echo "<form name='". $this->formulario['name'] ."' action='". $this->formulario['action'] ."' align=center method=". $this->formulario['method'] ."> ";
 		?>
 		<table cellspacing=0 <?= $this->atributos ."   ". ($this->clase ? " class='".$this->clase."'" : "")  ?> >
 		<?
 			if(isset($this->titulo))
 			{
 				echo "<caption><b>". $this->titulo ."</b></caption>";
 			}
 			$cont = 0;
 			while($row = mysql_fetch_array($this->resultado))
 			{
 				if($cont == 0)
 				{
 					echo "<tr> \n";
 					$pos = 0;
 					
 					foreach($this->campos as $campo => $label)
 					{
 						$pos++;
 						if(isset($this->columnas[$pos]['nombre']))
 						{
 							echo "<th ". $this->columnas[$pos]['atributos'] ." >". $this->columnas[$pos]['nombre'] ."</th>\n";	
 						}
 						echo "<th>$label</th>\n";		
 					}
 					
 					foreach($this->columnas as $posicion => $valor)
 					{
 						if($posicion > $pos)
 						{
 							echo "<th ". $this->columnas[$posicion]['atributos'] ." >". $this->columnas[$posicion]['nombre'] ."</th>\n";
 						}
 					}
 					
 					
 					echo "</tr>\n";
 				} 
 				
 				$cont++;

				//all row link 				
 				if($this->rowLink != "")
 				{

 				}
 				
 				echo "<tr class='report-row' " . $this->rowAtts . ">\n";
 				$pos = 0;
 					foreach($this->campos as $campo => $label)
 					{
 						$pos++;
 						if(isset($this->columnas[$pos]['nombre']))
 						{
 							echo "<td ". $this->columnas[$pos]['atributos'] ." >". $this->aplicarValor($this->columnas[$pos]['valor'],$row) ."</td>\n";	
 						}	
 						
 						if(isset($this->links[$campo]))
 						{
 							$link = $this->links[$campo];
 							
 							foreach($row as $field => $val)
 							{
 								$link = str_replace("[$field]",$this->darFormato($row[$field],$field),$link);
 							} 							
 								echo "<td ". $this->atributosCampos[$campo] ."><a href='". $link ."'>". $this->darFormato($row[$campo],$campo) ."</a></td>\n";
 								 							
								//$this->totales[$campo]+=$row[$campo];
 								
 						}
 						else
 						{
 								echo "<td ". $this->atributosCampos[$campo] .">". $this->darFormato($row[$campo],$campo) ."</td>\n";
 						}	
 						if(in_array($campo,array_keys($this->totales)))
						{
							if(is_numeric($this->totales[$campo]))
								$this->totales[$campo]+=$row[$campo];
						}
 					}
 					
 					foreach($this->columnas as $posicion => $valor)
 					{
 						if($posicion > $pos)
 						{
 							echo "<td ". $this->columnas[$posicion]['atributos'] ." >". $this->aplicarValor($this->columnas[$posicion]['valor'],$row) ."</td>\n";
 						}
 					}
 					
 					echo "</tr></a>\n";
 					
 					//all row link
 					if($this->rowLink != "")
	 				{
	 					 	 					
	 				}
 				
 			}
 			if($cont == 0)
 			{
 				echo "<tr> \n";
 					$pos = 0;
 					foreach($this->campos as $campo => $label)
 					{
 						$pos++;
 						if(isset($this->columnas[$pos]['nombre']))
 						{
 							echo "<th ". $this->columnas[$pos]['atributos'] ." >". $this->columnas[$pos]['nombre'] ."</th>\n";	
 						}
 						echo "<th>$label</th>\n";		
 					}
 					echo "</tr>\n";
 				
 			}
 			
 			
 			
 		if(count($this->totales))
 		{
 			echo "<tr class=totals>";
	 		$pos=0;
	 		foreach($this->campos as $campo => $label)
	 		{
	 			$pos++;
	 			
	 			if(isset($this->columnas[$pos]['nombre']))
				{
					
					//echo "<td> $campo--"  . join(array_keys($this->formatoTotales),",").  "<br></td>"; 
					
					
					if(isset($this->formatoTotales[$campo]))
					{
						echo "<td ". $this->columnas[$pos]['atributos'] ." >". $this->formatoTotales[$campo](($this->totales[$this->columnas[$pos]['nombre']])) ."</td>\n";
					}
					else
					{
						echo "<td ". $this->columnas[$pos]['atributos'] ." >". format_number($this->totales[$this->columnas[$pos]['nombre']]) ."</td>\n";
					}	
				}

				if(isset($this->totales[$campo]))
				{
					//echo "<td> $campo--"  . join(array_keys($this->formatoTotales),",") .  "<br>";
					//	print_r($this->formatoTotales);
					//echo "</td>";
					
					/*if(isset($this->formatoTotales[$campo]))
					{
	 					echo "<td ". $this->columnas[$pos]['atributos'] .">". $this->formatoTotales[$campo]($this->totales[$campo]) ."</td>";
					}
					else
					{
						echo "<td ". $this->columnas[$pos]['atributos'] .">". format_number($this->totales[$campo]) ."</td>";
					}*/
					
					if(isset($this->formatoTotales[$campo]))
					{
						if($campo == "mtd_avg")
						{
							$value = ($this->totales["units"] > 0 ? $this->totales["gross_total"] / $this->totales["units"] : 0); 
							//echo "<td ". $this->columnas[$pos]['atributos'] .">". $this->formatoTotales[$campo]($value) ."</td>";	
							echo "<td ". $this->columnas[$pos]['atributos'] .">". $this->formatDolar($value, '') ."</td>";
						}	
						else
							if($campo == "gross_front" || $campo == "gross_back" || $campo == "gross_total")
								echo "<td ". $this->columnas[$pos]['atributos'] .">". $this->formatDolar($this->totales[$campo], '') ."</td>";
							else
								if($campo == "close_sh%")
								{
									$value = 0;
									$value = ($this->totales['showroom'] > 0) ? (($this->totales['sold_sh'] * 100) /$this->totales['showroom']) : 0;
									echo "<td align='center' ". $this->columnas[$pos]['atributos'] .">". formatPercentage2($value) ."</td>";
								}
								else
									if($campo == "close_ip%")
									{
										$value = 0;
										$value = ($this->totales['iphone'] > 0) ? (($this->totales['sold_ip'] * 100) /$this->totales['iphone']) : 0;
										echo "<td align='center' ". $this->columnas[$pos]['atributos'] .">". formatPercentage2($value) ."</td>";
									}
									else
										if($campo == "close_il%")
										{
											$value = 0;
											$value = ($this->totales['ileads'] > 0) ? (($this->totales['sold_il'] * 100) /$this->totales['ileads']) : 0;
											echo "<td align='center' ". $this->columnas[$pos]['atributos'] .">". formatPercentage2($value) ."</td>";
										}
										else
				 							echo "<td ". $this->columnas[$pos]['atributos'] .">". $this->formatoTotales[$campo]($this->totales[$campo]) ."</td>";
					}
					else
					{
						if($campo == "mtd_avg")
						{
							$value = ($this->totales["units"] > 0 ? $this->totales["gross_total"] / $this->totales["units"] : 0);
							echo "<td ". $this->columnas[$pos]['atributos'] .">". $this->formatDolar($value, '') ."</td>";
						}	
						else
							if($campo == "gross_front" || $campo == "gross_back" || $campo == "gross_total")
								echo "<td ". $this->columnas[$pos]['atributos'] .">". $this->formatDolar($this->totales[$campo], '') ."</td>";
							else
								if($campo == "close_sh%")
								{
									$value = 0;
									$value = ($this->totales['showroom'] > 0) ? (($this->totales['sold_sh'] * 100) /$this->totales['showroom']) : 0;
									echo "<td align='center' ". $this->columnas[$pos]['atributos'] .">".formatPercentage2($value) ."</td>";
								}
								else
									if($campo == "close_ip%")
									{
										$value = 0;
										$value = ($this->totales['iphone'] > 0) ? (($this->totales['sold_ip'] * 100) /$this->totales['iphone']) : 0;
										echo "<td align='center' ". $this->columnas[$pos]['atributos'] .">".formatPercentage2($value) ."</td>";
									}
									else
										if($campo == "close_il%")
										{
											$value = 0;
											$value = ($this->totales['ileads'] > 0) ? (($this->totales['sold_il'] * 100) /$this->totales['ileads']) : 0;
											echo "<td align='center' ". $this->columnas[$pos]['atributos'] .">".formatPercentage2($value) ."</td>";
										}
										else		
											echo "<td ". $this->columnas[$pos]['atributos'] .">". format_number($this->totales[$campo]) ."</td>";
					}
				}
	 			else
	 				echo "<td ". $this->columnas[$pos]['atributos'] ."></td>";
	 		}
	 		echo "</tr>";
 		}
 			
 		if($this->close)
 		{
 			$this->closeAll();
 		}
 	}
 	
 	
 	public function setTotalsFormat($fields)
 	{
 		$this->formatoTotales = $fields;
 				
 	}
 	
 	public function closeComponent($value)
 	{
 		$this->close = $value;
 	}
 	
 	
 	function crearRowLink($link)
 	{
 		$this->rowLink  = $link;
 		 		
 		foreach($this->campos as $campo => $value)
 		{
 			$this->crearLink($campo,$link);
 		}
 		
 	}
 	
 	public function closeAll()
 	{
 			
 		echo "</table>";
 		if(isset($this->formulario['name']))
 			echo $this->formulario['input']."</form>";
 		echo "</div>";
 	}
 	
 	function aplicarValor($valor,$fila)
 	{
 		foreach($fila as $campo => $val)
 		{
 			$valor = str_replace("[$campo]",$val,$valor);
 		}
 		
 		return $valor;
 	}
 	
 	
 	function darFormato($dato, $campo)
 	{
 		 		
 		if(isset($this->formatos[$campo]['funcion']))
 		{
 			 $funcion = $this->formatos[$campo]['funcion'];
 			return  $funcion($dato,$this->formatos[$campo]['params']);
 		}
 		else 
 			return $dato;
 	}
 } 
 
 function formatPercentage2($value)
{
	return number_format($value, 0)."%";
}
		 
 function format_number($value)
 {
 	if(is_numeric($value))
 	{
 		if(strpos($value,"."))
 			return number_format  ( $value,2,'.',',');
 		else
 			return number_format  ( $value,0,'.',',');
 	}
 	else
 		return $value; 
 }
 
?>
