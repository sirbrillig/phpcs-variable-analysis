# PHP_CodeSniffer VariableAnalysis

Plugin for PHP_CodeSniffer static analysis tool that adds analysis of problematic variable use.

 * Performs static analysis of variable use.
 * Warns on use of undefined variables.
 * Warns if variables are set or declared but never used within that scope.
 * Warns if variables are redeclared within same scope.
 * Warns if $this, self::$static_member, static::$static_member is used outside class scope.

## Installation

### Requirements

VariableAnalysis requires PHP 5.4 or higher and [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) version **3.0.2** or higher.

### With PHPCS Composer Installer

This is the easiest method.

First, install [phpcodesniffer-composer-installer](https://github.com/DealerDirect/phpcodesniffer-composer-installer) for your project if you have not already. This will also install PHPCS.

```
composer require --dev dealerdirect/phpcodesniffer-composer-installer
```

Then install these standards.

```
composer require --dev sirbrillig/phpcs-variable-analysis
```

You can then include the sniffs by adding a line like the following to [your phpcs.xml file](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Advanced-Usage#using-a-default-configuration-file).

```
<rule ref="VariableAnalysis"/>
```

It should just work after that!

#### Troubleshooting

If you see an error like this when trying to install the library, you will need to include stability information in your composer.json.

```
- sirbrillig/phpcs-variable-analysis dev-master requires roave/security-advisories dev-master -> satisfiable by roave/security-advisories[dev-master] but these conflict with your requirements or minimum-stability.
```

To fix this, add the following lines to your composer.json file and then try installing again.

```
"minimum-stability": "dev",
"prefer-stable": true,
```

### Standalone

1. Install PHP_CodeSniffer by following its [installation instructions](https://github.com/squizlabs/PHP_CodeSniffer#installation) (via Composer, Phar file, PEAR, or Git checkout).

   Do ensure that PHP_CodeSniffer's version matches our [requirements](#requirements)

2. Clone the repository:

        git clone -b master https://github.com/sirbrillig/VariableAnalysis.git VariableAnalysis

3. Add its path to the PHP_CodeSniffer configuration:

        phpcs --config-set installed_paths /path/to/VariableAnalysis

If you already have installed paths, [use a comma to separate them](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Configuration-Options#setting-the-installed-standard-paths).

## Customization

There's a variety of options to customize the behaviour of VariableAnalysis, take
a look at the included ruleset.xml.example for commented examples of a configuration.

## Original

This was forked from the excellent work in https://github.com/illusori/PHP_Codesniffer-VariableAnalysis

## Contributing

Please open issues or PRs on this repository.
