#!/usr/bin/env bash
set -e

#############################################################
# Basic usage:             bin/ci.sh
# Fix phpcs errors first:  bin/ci.sh -f
#############################################################

# Declare arguments and global vars
VAR_SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
VAR_WORKING_DIR="${VAR_SCRIPT_DIR}/.."

# TERMINAL COLORS
COL_RED='\033[1;31m'
COL_YELLOW='\033[1;33m'
COL_GREEN='\033[0;32m'
COL_BLUE='\033[0;34m'
COL_NC='\033[0m' # No Color

ARG_FIX_PHPCS="no"

parse_args()
{
    while [ $# -gt 0 ]
    do
        case "${1}" in
            -f)
                ARG_FIX_PHPCS="yes"
                shift
            ;;
            *)
                echo -e "\nERROR: UNKNOWN ARGUMENT ${1}\n"
                exit 1
            ;;
        esac
    done

    return
}
parse_args "$@"

# Main flow
function fnc_main()
{

    if [[ ${ARG_FIX_PHPCS} == "yes" ]]; then
        fnc_fix_phpcs
    fi

    fnc_lint_yaml
#    fnc_lint_twig
    fnc_phpunit
    fnc_phpcs
    fnc_phpmd
    fnc_phpstan
    fnc_psalm

    return
}

function fnc_fix_phpcs()
{
    echo -e "\n${COL_GREEN}START PHP-CS-FIXER${COL_NC}\n"

    (cd "${VAR_WORKING_DIR}" && PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix)

    echo -e "\n${COL_GREEN}END PHP-CS-FIXER${COL_NC}\n"

    return
}

function fnc_lint_yaml()
{
    echo -e "\n${COL_GREEN}START YAML LINT${COL_NC}\n"

    (cd "${VAR_WORKING_DIR}" && bin/console lint:yaml config)
    (cd "${VAR_WORKING_DIR}" && bin/console lint:yaml src)

    echo -e "\n${COL_GREEN}END YAML LINT${COL_NC}\n"

    return
}

function fnc_lint_twig()
{
    echo -e "\n${COL_GREEN}START TWIG LINT${COL_NC}\n"

    (cd "${VAR_WORKING_DIR}" && bin/console lint:twig src)

    echo -e "\n${COL_GREEN}END TWIG LINT${COL_NC}\n"

    return
}

function fnc_phpunit()
{
    echo -e "\n${COL_GREEN}START PHPUNIT${COL_NC}\n"

    (cd "${VAR_WORKING_DIR}" && bin/phpunit --configuration phpunit.xml.dist)

    echo -e "\n${COL_GREEN}END PHPUNIT${COL_NC}\n"

    return
}

function fnc_phpcs()
{
    echo -e "\n${COL_GREEN}START PHPCS${COL_NC}\n"

    (cd "${VAR_WORKING_DIR}" && vendor/bin/phpcs --report=checkstyle --extensions=php src tests)

    echo -e "\n${COL_GREEN}END PHPCS${COL_NC}\n"

    return
}

function fnc_psalm()
{
    echo -e "\n${COL_GREEN}START PSALM${COL_NC}\n"

    (cd "${VAR_WORKING_DIR}" && vendor/bin/psalm --show-info=true)

    echo -e "\n${COL_GREEN}END PSALM${COL_NC}\n"

    return
}

function fnc_phpmd()
{
    echo -e "\n${COL_GREEN}START PHPMD${COL_NC}\n"

    (cd "${VAR_WORKING_DIR}" && vendor/bin/phpmd src,tests text controversial,unusedcode)

    echo -e "\n${COL_GREEN}END PHPMD${COL_NC}\n"

    return
}

function fnc_phpstan()
{
    echo -e "\n${COL_GREEN}START PHPSTAN${COL_NC}\n"

    (cd "${VAR_WORKING_DIR}" && php -d memory_limit=-1 vendor/bin/phpstan analyse src tests)

    echo -e "\n${COL_GREEN}END PHPSTAN${COL_NC}\n"

    return
}

# RUN
fnc_main "$@"
