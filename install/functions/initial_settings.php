<?php
exit;
?>
;///////////////////////////////////////////////////////////////////////////////
;
; NagiosQL Service Pack 3
;
;///////////////////////////////////////////////////////////////////////////////
;
; (c) 2016 by Fabio Lucchiari
; 
; Project   : NagiosQL Service Pack 3
; Component : Configuration verification
; Website   : https://tecnologicasduvidas.blogspot.com.br
; Date      : $LastChangedDate: 2017-01-02 16:00:00 -0300$
;
; DO NOT USE THIS FILE AS NAGIOSQL SETTINGS FILE!
;
;///////////////////////////////////////////////////////////////////////////////
[db]
type			= mysqli
server			= localhost
port			= 3306
database		= db_nagiosql_v32
username		= nagiosql_user
password		= nagiosql_pass
[path]
protocol		= http
tempdir			= /tmp
base_url		= /
base_path		= ''
[data]
locale			= en_GB
encoding		= utf-8
[security]
logofftime		= 3600
wsauth			= 0
[common]
pagelines		= 15
seldisable		= 1
tplcheck		= 0
updcheck		= 1
[network]
proxy			= 0
proxyserver 	= ''
proxyuser		= ''
proxypasswd 	= ''
onlineupdate	= 0