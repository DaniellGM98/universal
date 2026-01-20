
<?php
	// MODULO USUARIOS
	define('MOD_USUARIOS',			1);
	define('MOD_USUARIOS_ADD',		2);
	define('MOD_USUARIOS_EDIT',		3);
	define('MOD_USUARIOS_PERM',		4);
	define('MOD_USUARIOS_DEL',		5);
	define('MOD_USUARIOS_BAJA',		6);
	define('MOD_USUARIOS_NOBLOQUEO',7);
	define('MOD_USUARIOS_LOG',		77);

	// MODULO PROVEEDORES
	define('MOD_PROVEEDOR',			8);
	define('MOD_PROVEEDOR_ADD',		9);
	define('MOD_PROVEEDOR_EDIT',	10);
	define('MOD_PROVEEDOR_DEL',		11);

	// MODULO PRODUCTOS
	define('MOD_PRODUCTOS',			12);
	define('MOD_PRODUCTOS_ADD',		13);
	define('MOD_PRODUCTOS_EDIT',	14);
	define('MOD_PRODUCTOS_DEL',		15);
	// define('MOD_PRODUCTOS_PRECIO',	16);
	// define('MOD_PRODUCTOS_ENTRADA',	17);

	// MODULO CLIENTES
	define('MOD_CLIENTES',			16);
	define('MOD_CLIENTES_ADD',		17);
	define('MOD_CLIENTES_EDIT',		18);
	define('MOD_CLIENTES_DEL',		19);

	// MODULO ALMACEN GENERAL
	define('MOD_ENTRADAS_ALM', 			20);
	define('MOD_ENTRADAS_ALM_ADD', 		21);
	define('MOD_ENTRADAS_ALM_DEL', 		22);
	define('MOD_ENTRADAS_ALM_DET_DEL', 	23);

	define('MOD_SALIDAS_ALM', 			24);
	define('MOD_SALIDAS_ALM_ADD', 		25);
	define('MOD_SALIDAS_ALM_DEL', 		26);
	define('MOD_SALIDAS_ALM_DET_DEL', 	27);
	
	define('MOD_KARDEX_ALM', 			28);

	// MODULO COMUNIDADES
	define('MOD_COMUNIDAD_ADD', 		47);
	define('MOD_COMUNIDAD_EDIT', 		48);
	define('MOD_COMUNIDAD_DEL', 		49);

	define('MOD_ENTRADAS_COM', 			29);
	define('MOD_ENTRADAS_COM_ADD', 		30);
	define('MOD_ENTRADAS_COM_DEL', 		31);
	define('MOD_ENTRADAS_COM_DET_DEL', 	32);

	define('MOD_SALIDAS_COM', 			33);
	define('MOD_SALIDAS_COM_ADD', 		34);
	define('MOD_SALIDAS_COM_DEL', 		35);
	define('MOD_SALIDAS_COM_DET_DEL', 	36);
	
	define('MOD_KARDEX_COM', 			37);

	// MODULO PRODUCCION
	define('MOD_ENTRADAS_PROD', 		38);
	define('MOD_ENTRADAS_PROD_ADD', 	39);
	define('MOD_ENTRADAS_PROD_DEL', 	40);
	define('MOD_ENTRADAS_PROD_DET_DEL', 41);

	define('MOD_SALIDAS_PROD', 			42);
	define('MOD_SALIDAS_PROD_ADD', 		43);
	define('MOD_SALIDAS_PROD_DEL', 		44);
	define('MOD_SALIDAS_PROD_DET_DEL', 	45);
	
	define('MOD_KARDEX_PROD', 			46);


	// MODULO TIENDITA
	define('MOD_ENTRADAS_TIEN', 		50);
	define('MOD_ENTRADAS_TIEN_ADD', 	51);
	define('MOD_ENTRADAS_TIEN_DEL', 	52);
	define('MOD_ENTRADAS_TIEN_DET_DEL', 53);

	define('MOD_VENTAS_TIEN', 			54);
	define('MOD_VENTAS_TIEN_ADD', 		55);
	define('MOD_VENTAS_EDIT_TIPO', 		78);
	define('MOD_VENTAS_TIEN_DEL', 		56);
	define('MOD_VENTAS_TIEN_DET_DEL', 	57);
	
	define('MOD_KARDEX_TIEN', 			58);

	define('MOD_EXISTENCIAS_TIEN', 		59);
	define('MOD_TRANSFER_TIEN', 		60);
	define('MOD_TRANSFER_TIEN_ADD', 	61);
	define('MOD_TRANSFER_TIEN_DEL', 	62);
	
	
	// REPORTES
	define('RPT_ALMACEN_STOCK', 		63);
	define('RPT_ALMACEN_ENTRADAS', 		64);
	define('RPT_ALMACEN_ENTRADAS_PROV', 65);
	define('RPT_ALMACEN_ENTRADAS_CVE', 	66);
	define('RPT_ALMACEN_SALIDAS', 		67);
	define('RPT_ALMACEN_SALIDAS_PROV', 	68);
	define('RPT_ALMACEN_SALIDAS_CVE', 	69);
	define('RPT_ALMACEN_SALIDAS_CLI', 	70);
	
	define('RPT_COMUNIDAD_ENTRADAS', 	71);
	define('RPT_COMUNIDAD_SALIDAS', 	72);

	define('RPT_PRODUCCION_ENTRADAS', 	73);
	define('RPT_PRODUCCION_SALIDAS', 	74);

	define('RPT_TIENDITA_ENTRADAS', 	75);
	define('RPT_TIENDITA_VENTAS', 		76);


?>