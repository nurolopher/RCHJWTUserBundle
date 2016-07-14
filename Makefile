cs:
	./vendor/bin/php-cs-fixer fix --verbose --config-file=.php_cs

cs_dry_run:
	./vendor/bin/php-cs-fixer fix --verbose --dry-run

test:
	phpunit
