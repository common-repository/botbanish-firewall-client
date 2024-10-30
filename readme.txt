=== BotBanish by Randem Systems ===
Contributors: Randem06
Tags: botbanish, wordpress , bot banish, bot defense, bot spam, spammer bot, spam, fake account, bad user, firewall, firewall client
Requires at least: 4.1.1
Tested up to: 6.4.3
Stable tag: 5.0.00
Requires PHP: 7.4
Donate link: https://square.link/u/jy0LWKCQ
Purchase link: https://square.link/u/xudEE2tp

== Description ==

= BotBanish Firewall Client Plugin =

//////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Randem Systems "BotBanishClient" Installation Instructions ver 5.x
// SMF mod package for use with the Simple Machines Forum version 2.1.4, PHP, mySQL & Apache
// For more information and support.
//
// Author - Randem (http://www.simplemachines.org/community/index.php?action=profile;u=384160)
//
// BotBanish Website: https://botbanish.com
// BotBanish Support: https://randemsystems.support/index.php#c18
// Release History: https://randemsystems.support/botbanish-release-history/
// Date: 02/24/2024
/////////////////////////////////////////////////////////////////////////////////////////////////////////////

* * * WARNING * * *

	BotBanish is designed to modify your root and SMF .htaccess file. Upon installation, BotBanish will automatically create a backup of your existing .htaccess file for safekeeping.
	As a best practice, it is recommended to initiate the process with a clean .htaccess file and then carefully reintegrate your custom entries following the installation.
	The individual tasked with overseeing BotBanish for SMF must possess a robust understanding of PHP, FTP, and essential .htaccess commands.
	With the introduction of BotBanish version 5.0.00, featuring an interface exclusively accessible to administrators, the maintenance of BotBanish table entries,
	which are instrumental in governing the functionality of the system, will be significantly facilitated.

* * * * * * * * * *

Introducing the BotBanish Firewall Client: A Robust, Cutting-Edge Solution for Superior Network Protection:

BotBanishClient is a sophisticated software solution designed to safeguard your digital infrastructure. It diligently monitors bot and user activity, effectively identifying and mitigating malicious attempts to compromise your system. This entails thwarting brute force attacks, preventing the creation of illegitimate user accounts, and detecting vulnerabilities that could be exploited. Furthermore, BotBanishClient provides a robust defense against Distributed Denial of Service (DDoS) attacks, curtails unauthorized data extraction, and prevents bandwidth exploitation, ensuring comprehensive protection for your website from a multitude of cyber threats.

How BotBanish Works:

	BotBanish continuously monitors every attempt to access the system, identifying specific URL phrases indicative of attempts to exploit vulnerabilities.
	Upon detecting such activity, BotBanish swiftly responds by updating the .htaccess file to block the offending IP address, preventing further attacks from that source.
	Recognizing that IP addresses can be dynamic, this proactive measure may inadvertently block legitimate users. To address this, it is recommended to establish a
	clear process for users to contact the system administrator and request an unblock should they be affected by a shifted IP previously associated with a malicious entity.
	System administrators can then promptly remove the IP address from the BotBanish blacklist. For lasting protection, legitimate IP addresses should be added to the BotBanish whitelist, ensuring uninterrupted access. To unblock an IP, administrators simply need to remove the entry from the BotBanish blacklist.

Uses:

	BotBanish seamlessly integrates providing automatic detection and termination of bots identified as malicious or counterfeit users.
	BotBanish efficiently logs such incidents for administrative review. By continuously monitoring and restricting access to unauthorized entities,
	BotBanish enhances system security without burdening resources, a proactive measure allowing your website to focus on performance rather than
	defense against repetitive bot incursions. For optimal functionality, please ensure that the following line is removed from your
	.htaccess file as it may disrupt the processing of user agents:

	RewriteCond %{HTTP_USER_AGENT} ^(.+)$

Limitations:

    This software is exclusively engineered for the PHP, MySQL, and Apache environments. It is optimized for PHP 8.1.x, Apache 2.4.x, and MySQL 5.7.x,
	ensuring peak performance and stability. To maintain compatibility, the minimum required versions are PHP 7.4.x, Apache 2.2.x, and MySQL 5.6.x.

Analytics:

    BotBanish meticulously gathers data on visitor interactions, tracking page views and file downloads from your website.
	Access these valuable insights by choosing 'Analytics' from the BotBanish dashboard. For optimal analytical performance, we recommend utilizing SEO-friendly URLs.

Installation Instructions:


	Upload the BotBanish zip file to the WordPress Plugins folder was installed or use the WordPress "Add Plugin" menu selection to upload the Botbanish zip file.
	
	1) Log into your WordPress dashboard.
	2) Go to Plugins to upload and install the BotBanish Plugin

Languages:

    BotBanish is presently equipped to offer support in these languages:

	English
	French
	German
	Italian
	Portuguese
	Spanish
	Swedish

Before installing the new version, it is imperative to uninstall the previous version. Rest assured, no functionality will be compromised,
provided you opt not to remove data during the uninstall process.

== Installation ==

	Upload the BotBanish zip file to the WordPress Plugins folder was installed or use the WordPress "Add Plugin" menu selection to upload the Botbanish zip file.


Settings can be changed on the BotBanish Firewall Client Settings Page

	BotBanish Website:	https://botbanish.com
	BotBanish Support:	https://randemsystems.support/index.php#c18
	Release History: 	https://randemsystems.support/botbanish-release-history/


== Changelog ==

= 3.0.00 - 2018-02-05 =
= 3.2.06 - 2019-04-13 =

== Upgrade Notice ==

= 3.0.00 2018-02-05 =
	Release History: https://randemsystems.support/botbanish-release-history/

= 3.2.06 2018-02-05 =
	Release History: https://randemsystems.support/botbanish-release-history/

= 3.3.00 2018-11-05 =
	Release History: https://randemsystems.support/botbanish-release-history/

= 3.4.00 2018-11-05 =
	Release History: https://randemsystems.support/botbanish-release-history/

= 4.0.01 2022-07-29 =
	Release History: https://randemsystems.support/botbanish-release-history/

= 4.1.00 2023-02-24 =
	Release History: https://randemsystems.support/botbanish-release-history/

= 4.1.01 2023-05-24 =
	Release History: https://randemsystems.support/botbanish-release-history/

= 4.1.03 2023-06-30 =
	Release History: https://randemsystems.support/botbanish-release-history/

= 5.0.00 2024-03-07 =
	Release History: https://randemsystems.support/botbanish-release-history/

== Screenshots ==

1. None

== FAQ ==

None