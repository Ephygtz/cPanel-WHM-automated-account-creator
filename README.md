###############################################################
# cPanel-WHM-automated-account-creator
Will automatically create user account in cPanel/WHM, create subdomain in WHM and preconfigure hosting package automatically i.e ftp, quota, email
###############################################################
# cPanel WHM Account Creator
###############################################################
 Required parameters:
 - domain - new account domain
 - user - new account username
 - password - new account password
 - package - new account hosting package (plan)
 - email - contact email
#
> Sample run: create-whm-account.php?domain=reseller.com&user=hosting&password=manager&package=unix_500

> If no parameters passed then input form will be shown to enter data.

> This script can also be run from another PHP script. This may
> be helpful if you have some user interface already in place and
> want to automatically create WHM accounts from there.
> In this case you have to setup following variables instead of
> passing them as parameters:
 - $user_domain - new account domain
 - $user_name - new account username
 - $user_pass - new account password
 - $user_plan - new account hosting package (plan)
 - $user_email - contact email

###############################################################
