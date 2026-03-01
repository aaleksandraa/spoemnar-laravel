# Bugfix Requirements Document

## Introduction

The production site is experiencing a critical 500 Server Error caused by the SEO structured data Blade component. The component template attempts to use `$this->getJsonLd()` inside a `@php` block, which is not allowed in Blade components because `$this` is not available in that context. This bug causes the entire site to be inaccessible and requires immediate resolution.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN the SEO structured data Blade component renders THEN the system throws "Using $this when not in object context" error at line 2

1.2 WHEN the template attempts to call `$this->getJsonLd()` inside a `@php` block THEN the system crashes with a 500 Server Error

1.3 WHEN any page using the SEO structured data component loads THEN the system fails to render and returns an error response

### Expected Behavior (Correct)

2.1 WHEN the SEO structured data Blade component renders THEN the system SHALL successfully call the `getJsonLd()` method without errors

2.2 WHEN the template needs to access the component's `getJsonLd()` method THEN the system SHALL use a Blade-compatible syntax that works in component templates

2.3 WHEN any page using the SEO structured data component loads THEN the system SHALL render successfully with the JSON-LD structured data included

### Unchanged Behavior (Regression Prevention)

3.1 WHEN the `getJsonLd()` method is called THEN the system SHALL CONTINUE TO return the same JSON-LD structured data format

3.2 WHEN the component is included in pages THEN the system SHALL CONTINUE TO output the structured data in the same location in the HTML

3.3 WHEN the component receives props or attributes THEN the system SHALL CONTINUE TO process them in the same manner

3.4 WHEN the JSON-LD data is rendered THEN the system SHALL CONTINUE TO use the same script tag format and content structure
