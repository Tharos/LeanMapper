# How to contribute

There are several ways to help out:

* Create an issue on GitHub, if you have found a bug
* Write test cases for open bug issues
* Write fixes for open bug/feature issues, preferably with test cases included
* Contribute to the [documentation](https://github.com/LeanMapper/LeanMapper.github.io) or [examples](https://github.com/LeanMapper/examples)


## Issues

A good bug report shouldn't leave others needing to chase you up for more
information. Please try to be as detailed as possible in your report.

**Feature requests** are welcome. But take a moment to find out whether your idea
fits with the scope and aims of the project. It's up to *you* to make a strong
case to convince the project's developers of the merits of this feature.


## Contributing

### Preparing environment

Start with [forking](https://help.github.com/articles/fork-a-repo) [Lean Mapper on GitHub](https://github.com/Tharos/LeanMapper).
Carefully [set up](https://help.github.com/articles/set-up-git) your local Git environment, configure your username and email, these credentials will identify your changes in Lean Mapper history.


### Working on your patch

Before you start working on your patch, create a new branch for your changes.

```
git checkout -b new_branch_name
```


### Testing your changes

You need to install [Nette Tester](https://tester.nette.org/). The easiest way is to call `composer install` in repository root.

Now you should be able to run tests. On unix-like systems run following command in your terminal:

```
vendor/bin/tester -p php -c tests/php-unix.ini
```

**Note:** the tests require these PHP extensions:

* `json`
* `sqlite3`
* `tokenizer`

----

Please do not fix whitespace, format code, or make a purely cosmetic patch.

Thanks!
