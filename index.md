# Roundcube fetchmail plugin

**Roundcube fetchmail plugin** is a **Roundcube** plugin, which allows users to download their mail from external mailboxes.

## Screenshot

![Screenshot](http://pf4public.github.io/fetchmail/images/scrn.PNG)

## Prerequisites

1. **Roundcube**
2. Database (**PostgreSQL** or **MySQL**)
3. **fetchmail** itself
4. **Postfix Admin** provides convenient `fetchmail.pl` script

## Installation

Please follow these steps and adapt them to your distribution if necessary

1. First you need to install **fetchmail** itself. For **Debian** you can do so by `apt install fetchmail`
2. Next you should extract **Roundcube fetchmail plugin** archive into your **Roundcube** `plugins` folder creating "fetchmail" folder there.
  * You can do so either by using `composer` for which there is `composer.json`, still you need to follow further installation steps since those could not be accomplished with `composer`
  * Alternatively you can download needed release from [Releases page](https://github.com/PF4Public/fetchmail/releases) unpacking it accordingly
3. After that you need to enable newly installed plugin by adding it to **Roundcube** plugin list. For **Debian** related config file is `/etc/roundcube/main.inc.php` and relevant setting is 

	```php
	
	$rcmail_config ['plugins'] = array();
	
	```
Appending `, 'fetchmail'` to the list of plugins will suffice.

4. Unless default settings are suitable for you, you need to configure the plugin. See the [settings section](#settings) for more information.
5. You need to create additional table in your database using one of the supplied `.sql` files. Another possibility is to use **Postfix Admin** table if you have it installed. If using **PostgreSQL** you may use schemas to share `fetchmail` table between **Roundcube** and **Postfix Admin**. Namely creating it in `public` schema, whereas every other table in it's appropriate schema, like `roundcube` and `postfixadmin`. Please refer to [the documentation](https://www.postgresql.org/docs/current/static/ddl-schemas.html) for more information. If you do so and use composer, however, you probably need to set the database version of this plugin in roundcube database to `9999999900` so that composer will not try updating it.
6. You will need `fetchmail.pl` script from **Postfix Admin** distribution. If you don't have **Postfix Admin** installed, you can obtain required `fetchmail.pl` script from their repo  [postfixadmin / ADDITIONS / fetchmail.pl](https://github.com/postfixadmin/postfixadmin/blob/master/ADDITIONS/fetchmail.pl). But be sure to get at least revision [8bad929](https://github.com/postfixadmin/postfixadmin/blob/8bad929a4490f93587ceb00b5931405586b5cc04/ADDITIONS/fetchmail.pl), at which proper handling of `active` field introduced. Place it to where apropriate. For example, where your mailboxes are, e.g. `/var/mail`.
7. Next adapt `fetchmail.pl` to your config. Most likely you want to change these settings:

	```perl
	# database backend - uncomment one of these
	our $db_type = 'Pg';
	#my $db_type = 'mysql';
	
	# host name
	our $db_host="127.0.0.1";
	# database name
	our $db_name="postfix";
	# database username
	our $db_username="mail";
	# database password
	our $db_password="CHANGE_ME!";
	```
8. Next step is to configure **cron** for regular mail checking with `crontab -u mail -e`. For example for 5 minute intervals add this: `*/5 * * * * /var/mail/fetchmail.pl >/dev/null`. Worth noting that even if you configure cron for a 5 minutes interval, fetchmail will still abide user configured checking interval. As a result setting bigger intervals here manifests them as intervals available to fetchmail, that is setting `0 * * * *` here overrides any user setting wich is less then hour
9. You might also need to install `liblockfile-simple-perl` and ( `libsys-syslog-perl` or `libunix-syslog-perl` ) on **Debian**-based systems.
10. Lastly there might be need to do `mkdir /var/run/fetchmail; chown mail:mail /var/run/fetchmail`

Please note that some commands might require superuser permissions

## Settings

In case you need to edit default-set settings, you may copy `config.inc.php.dist` to `config.inc.php` and edit setings as desired in the latter file, which will override defaults.
* `$rcmail_config ['fetchmail_limit']` limits the number of external mailboxes per user allowed. Default is `10`.
* `$rcmail_config ['fetchmail_folder']` whether to allow users to specify IMAP folder they wish to download mail from. Default is `false`.
* `$rcmail_config ['fetchmail_mda']` allows you to specify mda field for fetchmail. This could be useful in case you want to deliver downloaded mail via MDA or LDA directly, rather than forwarding via SMTP or LMTP. For more information please refer to [fetchmail manual](http://www.fetchmail.info/fetchmail-man.html) and [fetchmail.pl](https://sourceforge.net/p/postfixadmin/code/HEAD/tree/trunk/ADDITIONS/fetchmail.pl) script. Default is `''`, i.e. not used.

## License

This software distributed under the terms of the GNU General Public License as published by the Free Software Foundation

Further details on the GPL license can be found at http://www.gnu.org/licenses/gpl.html

By contributing to **Roundcube fetchmail plugin**, authors release their contributed work under this license

## Acknowledgements

### Original author

Arthur Mayer, https://github.com/flames

### List of contributors

For a complete list of contributors, refer to [Github project contributors page](https://github.com/PF4Public/fetchmail/graphs/contributors)

#### Currently maintained by

* [PF4Public](https://github.com/PF4Public)
