# I13e.SourceInjector :syringe:
[![Latest Stable Version](http://poser.pugx.org/i13e/sourceinjector/v)](https://packagist.org/packages/i13e/sourceinjector) [![Total Downloads](http://poser.pugx.org/i13e/sourceinjector/downloads)](https://packagist.org/packages/i13e/sourceinjector) [![Latest Unstable Version](http://poser.pugx.org/i13e/sourceinjector/v/unstable)](https://packagist.org/packages/i13e/sourceinjector) [![License](http://poser.pugx.org/i13e/sourceinjector/license)](https://packagist.org/packages/i13e/sourceinjector) [![PHP Version Require](http://poser.pugx.org/i13e/sourceinjector/require/php)](https://packagist.org/packages/i13e/sourceinjector)

## Inject HTML/JS/CSS-Snippets directly out of the Neos CMS backend into frontend pages

This package gives editors the ability to inject HTML, JS or CSS snippets into frontpages directly out of the backend.

## Installation

Run `composer require i13e/sourceinjector`.

## Usage

After installation you shall be able to place an "Injections" node into your document tree.

Afterwards add as many "Injection" nodes you like with as many "Code" nodes you wan't into it.

By default, only `Neos.Neos:Administrator` privileged users are able to administrate injections.
All other users should be given `I13e.SourceInjector:InjectionEditor` to make injections editable.

If you aren't using `head`, `closingHeadTag`, `body` or `closingBodyTag` please adjust the injection output to your likes.
See [Override/Page.fusion](Resources/Private/Fusion/Override/Page.fusion)
```
  headStart = I13e.SourceInjector:Action.Inject {
    @position = 'before head'
    position = 'head_start'
  }
  headEnd = I13e.SourceInjector:Action.Inject {
    @position = 'before closingHeadTag'
    position = 'head_end'
  }

  bodyStart = I13e.SourceInjector:Action.Inject {
    @position = 'before body'
    position = 'body_start'
  }
  bodyEnd = I13e.SourceInjector:Action.Inject {
    @position = 'before closingBodyTag'
    position = 'body_end'
  }
}
```

## Settings

There aren't many settings available.

```yaml
I13e:
  SourceInjector:
    enableDebugComments: false # Default: false, output HTML-Comments before & after each injection code
```

## Roadmap

In feature versions we'll maybe implement more features like:
* Integration of [KaufmannDigital.GDPR.CookieConsent](https://github.com/KaufmannDigital/KaufmannDigital.GDPR.CookieConsent)
* Exclusion rules

## Contribution

If you'd like to contribute simply create a pull-request.

---

Proudly developed in the Hanover Region
