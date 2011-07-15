#!/bin/bash

showhelp() {
echo "install.sh [-h] [-p <PEAR dir>] [-d <PHP_Codesniffer dir>]

Install the PHP_Codesniffer-VariableAnalysis sniffs into your PEAR install of PHP_Codesniffer.

    -h        Show this help and exit.
    -t        Also install test scripts (only use if you're using svn checkout of PHP_Codesniffer).
    -p <dir>  Location of your PEAR install, defaults to ~/pear if not supplied.
    -d <dir>  Location of your PHP_codesniffer dir, calculated from PEAR dir by default.";
exit 0;
}

while getopts "p:d:th" "OPTNAME";
do
    case $OPTNAME in
        t) INSTALL_TESTS=1 ;;
        p) PEAR_DIR=$OPTARG ;;
        d) PHP_CODESNIFFER_DIR=$OPTARG ;;
        h) showhelp    ;;
        *) exit -1     ;;
    esac
done
shift $(($OPTIND - 1))

if [ -z "$PEAR_DIR" ]; then
    PEAR_DIR="$HOME/pear"
fi
if [ -z "$PHP_CODESNIFFER_DIR" ]; then
    PHP_CODESNIFFER_DIR="${PEAR_DIR}/share/pear/PHP/CodeSniffer"
fi

#  Hack to expand relative dirs.
INSTALL_FROM_DIR=$(dirname $(php -r "echo realpath('$0');"))

SNIFFS_SUBDIR="Sniffs/CodeAnalysis"
TESTS_SUBDIR="Tests/CodeAnalysis"

#echo "Setting up links using PHP_Codesniffer dir \"${PHP_CODESNIFFER_DIR}\" and plugins from \"${INSTALL_FROM_DIR}\"."

ERRORS=0

#  TODO: support copy install
if [ -d "${PHP_CODESNIFFER_DIR}/Standards/Generic/${SNIFFS_SUBDIR}" ]; then
    echo "Installing Sniffs to ${PHP_CODESNIFFER_DIR}/Standards/Generic/${SNIFFS_SUBDIR}";
    for file in ${INSTALL_FROM_DIR}/${SNIFFS_SUBDIR}/*.php;
    do
        cp -f $file ${PHP_CODESNIFFER_DIR}/Standards/Generic/${SNIFFS_SUBDIR}/`basename "$file"`
    done
else
    echo "Error installing sniffs: ${PHP_CODESNIFFER_DIR}/Standards/Generic/${SNIFFS_SUBDIR}/ does not exist."
    echo "Is PHP_Codesniffer installed to ${PHP_CODESNIFFER_DIR}? Try using -p option to your PEAR install, or -d to your PHP_Codesniffer dir."
    ERRORS=1
fi

if [ -n "$INSTALL_TESTS" ]; then
    if [ -d "${PHP_CODESNIFFER_DIR}/Standards/Generic/${TESTS_SUBDIR}" ]; then
        echo "Installing Tests to ${PHP_CODESNIFFER_DIR}/Standards/Generic/${TESTS_SUBDIR}";
        for file in ${INSTALL_FROM_DIR}/${TESTS_SUBDIR}/*.{php,inc};
        do
            cp -f $file ${PHP_CODESNIFFER_DIR}/Standards/Generic/${TESTS_SUBDIR}/`basename "$file"`
        done
    else
        echo "Error installing tests: ${PHP_CODESNIFFER_DIR}/Standards/Generic/${TESTS_SUBDIR}/ does not exist."
        echo "You should only install the tests if you are working from the SVN checkout of PHP_Codesniffer - the default install of PHP_Codesniffer does not includ the test suite."
        ERRORS=1
    fi
fi

exit $ERRORS
