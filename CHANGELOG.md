# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0-beta.10] - 2026-04-26

- Restore version and dependabot updates from the 2.x branch.

## [3.0.0-beta.9] - 2026-04-21

This is preparing for a major 3.0 release which adds a dependency on PHPCSUtils. The new version should have the same functionality, fix some subtle bugs, and increase performance by over 2x.

### Dependencies

- Add [PHPCSUtils](https://github.com/PHPCSStandards/PHPCSUtils) as a dependency.
- Bump minimum PHPCS version from 3.5.7 to 3.13.5.

### Refactoring (using PHPCSUtils)

- Simplify `getUseIndexForUseImport` using `findContainingOpeningBracket()`.
- Replace `findFunctionCallArguments` custom loop with `PassedParameters::getParameters()`.
- Replace `isConstructorPromotion` manual scan with `FunctionDeclarations::getParameters()`.
- Replace `areAnyConditionsAClass` manual loop with `Conditions::hasCondition()`.
- Simplify `getListAssignments` using `Lists::getAssignments`.
- Replace `findContainingOpeningBracket` with the PHPCSUtils equivalent.
- Replace `isVariableInsideUnset`/`isVariableInsideIssetOrEmpty` with PHPCSUtils equivalents.
- Simplify arrow function detection by relying on `T_FN`.
- Remove unnecessary `isArrowFunction` block in `getScopeCloseForScopeOpen`.

## [2.13.0] - 2025-09-30

- Ruleset: update schema URL (#353).
- Update for PHPCS 4.0.0 (#356).

## [2.12.0] - 2025-03-17

- Accept namespaces in `sitePassByRefFunctions` (#351).
- GH Actions: use the xmllint-validate action runner (#350).

## [2.11.22] - 2025-01-06

- Allow finding arrow function variables when the arrow function is in file scope (#347).
- Add perf-guard GitHub workflow (#345).
- Improve PHPStan configuration (#344).

## [2.11.21] - 2024-12-02

- Only search for nested arrow functions if necessary (#342).

## [2.11.20] - 2024-12-01

- Support nested arrow functions (#334).
- Simplify detecting class properties to improve detection for anonymous classes (#337).
- Handle `compact` inside arrow functions (#339).
- Support constructor promotion with namespaced and union typehints (#333).
- Various housekeeping: prevent a Composer lock file (#326), Psalm 5 (#327), PHPUnit 10/11 support (#328), PHP 8.4 in CI (#336), and run static analysis on PHP 8.1 (#338).

## [2.11.19] - 2024-06-26

- Fix: process variable before the end of scope (#324).
- GH Actions: work around intermittent apt-get errors (#322).

## [2.11.18] - 2024-04-18

- Switch to PHPCSStandards/PHP_CodeSniffer (#315).
- Make sure that recursive search of list assignments stays inside them (#318).
- Fix nullable constructor promotion detection (#321).

## [2.11.17] - 2023-08-07

- Do not close scope of ref reassignment when inside `else` (#306).

## [2.11.16] - 2023-03-31

- Allow newlines in arrow functions (#301).

## [2.11.15] - 2023-03-31

- Fix scope closer detection in arrow function detection (#298).

## [2.11.14] - 2023-03-30

- Use custom logic for finding arrow function scope (#296).

## [2.11.13] - 2023-03-30

- Support `readonly` constructor promotion (#294).

## [2.11.12] - 2023-03-13

- Allow non-enum tokens called 'enum' (#293).

## [2.11.11] - 2023-03-12

- Add support for enum methods (#289).

## [2.11.10] - 2023-01-06

- Composer: allow for the 1.0.0 version of the Composer PHPCS plugin (#287).

## [2.11.9] - 2022-10-05

- Prevent detecting static closures when looking for static variables (#281).

## [2.11.8] - 2022-09-08

- Fix function call detection to exclude non-function tokens (#279).

## [2.11.7] - 2022-08-16

- Place static arrow function body outside of scope on static declaration check (#276). Fixes a `static` variable regression from #267.

## [2.11.6] - 2022-08-15

Bug fix release for two regressions caused by the new static function variable scanning added in #267.

- Ignore function call arguments on static declarations processing (#273).
- Fix scanning for static variables near start of file (#274).

## [2.11.5] - 2022-08-14

- Allow `for` loop increment expression to use a variable defined inside the loop (#262).
- Support constructor promotion (#266).
- Improve static declaration checks (#267).
- Handle inline `if` list destructure (#268).
- Remove `php_errormsg` from superglobals list (#269).
- Ignore unused parameters when a function in an abstract class is empty (#270).

## [2.11.4] - 2022-07-21

- Fix a deprecation notice in PHP 8.2 (#248), plus internal housekeeping.

## [2.11.3] - 2022-02-21

- Update for Composer 2.2 (#242).

## [2.11.2] - 2021-07-06

- Respect ignoring undefined variables in `else` blocks (#239).

## [2.11.1] - 2021-06-18

- Allow parsing mixed HTML and PHP (#234, #236).
- Ignore double assignment by reference if the second is inside a condition (#235).

## [2.11.0] - 2021-03-15

- Start inline block scope at end of condition rather than at keyword (#230).
- Add `allowUnusedVariablesInFileScope` option (#229).

## [2.10.2] - 2021-01-18

- Treat writing to a by-reference `foreach` loop variable as a read (#221).
- Properly detect variables used in quotes inside arrow functions (#222).

## [2.10.1] - 2020-12-12

- Performance: only check one parent for recursive list assignment (#218). In at least one example file this brought the speed from 7 minutes down to 0.3 seconds.

## [2.10.0] - 2020-12-05

A large release that backports nearly all the improvements made on the 3.0 branch to the 2.x line, with the major exception of adding PHPCSUtils as a dependency.

Notable changes since 2.9:

- Fix `array_walk` pass-by-reference in 2.x (#216).
- Support for PHP 7.4 arrow functions (#179).
- Rewritten and more accurate scope detection.
- Allow undefined variables inside `isset` and `empty` (#204).
- Fix error in closures with namespace separators (#197).
- Fix complex default arguments (#198).
- Handle inline `if`/`else` (#199).
- Add `allowUndefinedVariablesInFileScope` option (#193).
- Add `allowUnusedVariablesBeforeRequire` option (#196).
- Support global scope (#190).
- Add `validUndefinedVariableRegexp` option (#173).
- Refactor and clean up reference variables (#187).
- Add special cases for variables used in `else` blocks (#189).

Note: this release intentionally excludes breaking changes from the 3.0 branch, namely replacing `UnusedVariable` with `UnusedParameter` for parameters (#195) and adding sniff codes for the array assignment shortcut (#205).

## [2.9.0] - 2020-10-07

Backports some 3.x features to the 2.x line.

- Add `allowUnusedVariablesBeforeRequire` option (originally #196).
- Add `allowUndefinedVariablesInFileScope` option (originally #193).

## [2.8.3] - 2020-07-11

- Downgrade requirements from PHP 5.6 to 5.4 (#191, backported to 2.x).

## [2.8.2] - 2020-05-31

A backport of the bugfixes from 3.0.0-beta.1 into the 2.x version without any of the PHPCSUtils changes.

- Fix comment tolerance for many sniffs.
- Clean up package structure.
- Only allow unused values in associative `foreach` loops with option (#167).
- Fix unused-before-used detection (#171).

## [2.8.1] - 2020-02-11

A small bug fix release with test and package clean-up.

- Make assignment of a reference variable be considered a use (#128).
- Bug fix: false negative unused var (#120).
- Bug fix: `$this` in nested function declaration (#119).
- Bug fix: recognize use of `self`/`static` within an anonymous class (#110).
- PHPStan: document run parameters in project ruleset (#122).
- Composer: update PHPCS Composer plugin dependency (#121), move PHPCS dependency to `require` (#106), update DealerDirect Composer plugin requirement (#112).
- Documentation: minor fixes (#123).
- Tests: simplify the PHPCS setup method (#115), allow them to work cross-platform (#108), add testsuite name to PHPUnit config (#113).
- Remove stray `composer.lock` file (#117).
- Composer: make the PHPUnit requirements more flexible (#114).
- PHPCS: document run parameters in project ruleset (#107).

## [2.8.0] - 2019-11-21

- Add `apcu_*` functions to `getPassByReferenceFunctions` (#103).
- Add sniff codes to README (#100).
- Mention phpcs-changed in README (#97).
- Update README to include PHPStan and linting (#96).
- Refactor methods to have nullable return types (#95).
- Tests: make sure fixtures are valid PHP.

## [2.7.0] - 2019-06-25

- Consider `get_defined_vars()` to be a read (#92).
- Use static analysis to remove edge case bugs (#94).

## [2.6.4] - 2019-05-10

- Recognize that variable variables are not assignments (#88).

## [2.6.3] - 2019-05-10

- Downgrade CircleCI image to PHP 5.6 (#86). Fixes a PHP 5.6 regression introduced in 2.6.2 and corrects the Composer minimum PHP version.

## [2.6.2] - 2019-04-23

- Allow global var assignment to count as a read (#83).

## [2.6.1] - 2019-04-01

- Improve check for static var defs inside functions (#80).
- Allow `self` and `static` references in a trait (#76).
- Add checks for class properties (#77).

## [2.6.0] - 2019-02-15

- Enable `allowUnusedForeachVariables` and `allowUnusedCaughtExceptions` (#73).

## [2.5.0] - 2019-02-08

- Add ignore unused `foreach` option (#66).
- Allow `self` within class method closures (#70).
- Add `allowWordPressPassByRefFunctions` option (#69).
- Document site pass by ref (#71).

## [2.4.0] - 2019-01-03

- Handle variable class static refs (#63).
- Ignore numeric variables (#61).
- Make `allowUnusedParametersBeforeUsed` default to true (#59).

## [2.3.0] - 2018-12-21

- Add `allowUnusedParametersBeforeUsed` option (#58).

## [2.2.0] - 2018-11-26

- Add option for undefined vars (#56).

## [2.1.3] - 2018-10-15

- Support shorthand list assignment (#50).
- Correctly mark pass-by-reference variables in `use()` constructs (#54).
- Support anonymous classes (#53).
- Build: rename the config files (#49).

## [2.1.2] - 2018-09-08

- Remove typehints to support PHP 5 (#47).

## [2.1.1] - 2018-08-06

- Include `parent` keyword as a static reference (#44).

## [2.1.0] - 2018-04-27

- Allow unused function arguments as regexp (#22).
- Treat trait properties as definitions (#40).

## [2.0.7] - 2018-02-13

- Fix broken `foreach` variable identification (#36). Fixes a major regression caused by #35.

## [2.0.6] - 2018-02-12

> ⚠️ **DO NOT USE THIS VERSION, IT HAS A MAJOR REGRESSION FIXED IN 2.0.7.**

- Allow list to declare vars in `foreach` (#35).

## [2.0.5] - 2018-02-09

- Fix recognition of static variable functions (#27).
- Simplify handling static variables (#32).
- Allow traits to use `$this` (#25).
- Remove null coalesce (#31).
- Move library files to the `Lib` directory and namespace (#28).
- Code cleanup (#29, #30) and test improvements (#23, #24).

## [2.0.4] - 2017-12-20

- Allow using `$this` in closures (#20).

## [2.0.3] - 2017-12-13

- Fix a bug that could occur where a variable was used within the same parenthesis-wrapped expression (e.g. function call arguments) but after a comma (#13).

## [2.0.2] - 2017-12-04

- Disable the `roave/security-advisories` dependency because it required developers to add additional lines to their `composer.json`.

## [2.0.1] - 2017-11-12

- Re-enable sniffs that were accidentally disabled, particularly unused function arguments.

## [2.0.0] - 2017-11-03

- Reorganize the source so it can be more easily referenced in a ruleset (#2).

## [1.0.0] - 2017-11-01

- Initial release.

[3.0.0-beta.10]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v3.0.0-beta.9...v3.0.0-beta.10
[3.0.0-beta.9]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.13.0...v3.0.0-beta.9
[2.13.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.12.0...v2.13.0
[2.12.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.22...v2.12.0
[2.11.22]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.21...v2.11.22
[2.11.21]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.20...v2.11.21
[2.11.20]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.19...v2.11.20
[2.11.19]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.18...v2.11.19
[2.11.18]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.17...v2.11.18
[2.11.17]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.16...v2.11.17
[2.11.16]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.15...v2.11.16
[2.11.15]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.14...v2.11.15
[2.11.14]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.13...v2.11.14
[2.11.13]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.12...v2.11.13
[2.11.12]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.11...v2.11.12
[2.11.11]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.10...v2.11.11
[2.11.10]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.9...v2.11.10
[2.11.9]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.8...v2.11.9
[2.11.8]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.7...v2.11.8
[2.11.7]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.6...v2.11.7
[2.11.6]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.5...v2.11.6
[2.11.5]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.4...v2.11.5
[2.11.4]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.3...v2.11.4
[2.11.3]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.2...v2.11.3
[2.11.2]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.1...v2.11.2
[2.11.1]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.11.0...v2.11.1
[2.11.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.10.2...v2.11.0
[2.10.2]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.10.1...v2.10.2
[2.10.1]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.10.0...v2.10.1
[2.10.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.9.0...v2.10.0
[2.9.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.8.3...v2.9.0
[2.8.3]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.8.2...v2.8.3
[2.8.2]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.8.1...v2.8.2
[2.8.1]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.8.0...v2.8.1
[2.8.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.7.0...v2.8.0
[2.7.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.6.4...v2.7.0
[2.6.4]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.6.3...v2.6.4
[2.6.3]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.6.2...v2.6.3
[2.6.2]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.6.1...v2.6.2
[2.6.1]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.6.0...v2.6.1
[2.6.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.5.0...v2.6.0
[2.5.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.4.0...v2.5.0
[2.4.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.3.0...v2.4.0
[2.3.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.2.0...v2.3.0
[2.2.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.1.3...v2.2.0
[2.1.3]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.1.2...v2.1.3
[2.1.2]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.1.1...v2.1.2
[2.1.1]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.1.0...v2.1.1
[2.1.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.0.7...v2.1.0
[2.0.7]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.0.6...v2.0.7
[2.0.6]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.0.5...v2.0.6
[2.0.5]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.0.4...v2.0.5
[2.0.4]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.0.3...v2.0.4
[2.0.3]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.0.2...v2.0.3
[2.0.2]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.0.1...v2.0.2
[2.0.1]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v2.0...v2.0.1
[2.0.0]: https://github.com/sirbrillig/phpcs-variable-analysis/compare/v1.0...v2.0
[1.0.0]: https://github.com/sirbrillig/phpcs-variable-analysis/releases/tag/v1.0
