# SEO Structured Data $this Context Fix - Bugfix Design

## Overview

The SEO structured data Blade component crashes with "Using $this when not in object context" error because the template incorrectly uses `$this->getJsonLd()` inside a `@php` block. In Laravel Blade components, `$this` is not available in the template context. Instead, component methods are automatically exposed as callable variables. The fix is minimal: change `$this->getJsonLd()` to `$getJsonLd()` in the template.

## Glossary

- **Bug_Condition (C)**: The condition that triggers the bug - when the Blade component template attempts to use `$this` to call a component method
- **Property (P)**: The desired behavior - the component should successfully render by calling the method using the correct Blade component syntax
- **Preservation**: The JSON-LD output format, script tag structure, and component behavior that must remain unchanged
- **getJsonLd()**: The method in `app/View/Components/SEO/StructuredData.php` that generates JSON-LD structured data based on the component's type and data
- **Blade Component Context**: In Laravel Blade components, methods are automatically exposed as callable variables in the template (e.g., `$methodName()` instead of `$this->methodName()`)

## Bug Details

### Fault Condition

The bug manifests when the SEO structured data Blade component renders. The template at `resources/views/components/seo/structured-data.blade.php` uses `$this->getJsonLd()` inside a `@php` block, which is invalid syntax for Blade component templates because `$this` is not available in that context.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type ComponentRenderContext
  OUTPUT: boolean
  
  RETURN input.template CONTAINS "$this->getJsonLd()"
         AND input.contextType == "BladeComponentTemplate"
         AND NOT input.hasThisContext
END FUNCTION
```

### Examples

- **Current (Buggy)**: Template uses `$this->getJsonLd()` → PHP Fatal Error: "Using $this when not in object context" → 500 Server Error
- **Expected (Fixed)**: Template uses `$getJsonLd()` → Method executes successfully → JSON-LD script tag renders correctly
- **Preservation Example**: The output `<script type="application/ld+json">{...}</script>` remains identical before and after the fix
- **Edge Case**: When `getJsonLd()` returns null (invalid schema), the component should render nothing (no script tag) - this behavior must be preserved

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- The `getJsonLd()` method logic must continue to generate the same JSON-LD structured data
- The script tag format `<script type="application/ld+json">` must remain unchanged
- The conditional rendering (`@if($jsonLd)`) must continue to work the same way
- Component props (type, data, breadcrumbs) must continue to be processed identically
- The component's render method must continue to return the same view
- All schema types (organization, website, person, breadcrumb) must continue to work

**Scope:**
All aspects of the component's functionality that do NOT involve the template syntax for calling `getJsonLd()` should be completely unaffected by this fix. This includes:
- The component class constructor and properties
- The getJsonLd() method implementation
- The StructuredDataService integration
- Schema generation and validation logic
- The HTML output structure and content

## Hypothesized Root Cause

Based on the bug description and code analysis, the root cause is clear:

1. **Incorrect Blade Component Syntax**: The template uses `$this->getJsonLd()` which is object-oriented PHP syntax, but Blade component templates do not have access to `$this`
   - In Laravel Blade components, the component instance is not directly accessible as `$this`
   - Component methods are automatically exposed as callable variables in the template
   - The correct syntax is `$getJsonLd()` (method name as a variable)

2. **Misunderstanding of Blade Component Context**: The developer likely assumed Blade component templates work like class methods where `$this` is available, but they actually work more like view files with injected variables

## Correctness Properties

Property 1: Fault Condition - Component Renders Without $this Error

_For any_ render context where the SEO structured data component is invoked, the fixed template SHALL successfully call the getJsonLd() method using the correct Blade component syntax (`$getJsonLd()` instead of `$this->getJsonLd()`), causing the component to render without throwing a "$this when not in object context" error.

**Validates: Requirements 2.1, 2.2, 2.3**

Property 2: Preservation - JSON-LD Output Unchanged

_For any_ component configuration (type, data, breadcrumbs) that previously would have generated JSON-LD output (if the bug didn't crash the system), the fixed component SHALL produce exactly the same JSON-LD structured data and HTML output as the original component would have produced, preserving the script tag format, content structure, and conditional rendering behavior.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4**

## Fix Implementation

### Changes Required

The root cause is confirmed: incorrect Blade component template syntax.

**File**: `resources/views/components/seo/structured-data.blade.php`

**Function**: Template rendering (line 2)

**Specific Changes**:
1. **Replace `$this->getJsonLd()` with `$getJsonLd()`**: Change the method call syntax from object-oriented to Blade component variable syntax
   - Line 2: Change `$jsonLd = $this->getJsonLd();` to `$jsonLd = $getJsonLd();`
   - This is the ONLY change required - no other files need modification

2. **No changes to component class**: The `app/View/Components/SEO/StructuredData.php` file remains unchanged
   - The `getJsonLd()` method is already public and will be automatically exposed to the template
   - Laravel's component system handles the method-to-variable conversion automatically

3. **No changes to service layer**: The `StructuredDataService` and schema generation logic remain unchanged

## Testing Strategy

### Validation Approach

The testing strategy follows a two-phase approach: first, confirm the bug exists on unfixed code by attempting to render the component, then verify the fix works correctly and preserves the exact JSON-LD output.

### Exploratory Fault Condition Checking

**Goal**: Surface counterexamples that demonstrate the bug BEFORE implementing the fix. Confirm that the template crashes with "$this when not in object context" error.

**Test Plan**: Write tests that attempt to render the component with various configurations. Run these tests on the UNFIXED code to observe the fatal error and confirm the root cause.

**Test Cases**:
1. **Organization Schema Test**: Render component with `type="organization"` (will fail on unfixed code with $this error)
2. **Website Schema Test**: Render component with `type="website"` (will fail on unfixed code with $this error)
3. **Person Schema Test**: Render component with `type="person"` and mock data (will fail on unfixed code with $this error)
4. **Breadcrumb Schema Test**: Render component with `type="breadcrumb"` and breadcrumb array (will fail on unfixed code with $this error)

**Expected Counterexamples**:
- Fatal error: "Using $this when not in object context" at line 2 of the template
- 500 Server Error when attempting to render any page using the component
- Possible cause: Incorrect Blade component template syntax using `$this->`

### Fix Checking

**Goal**: Verify that for all inputs where the bug condition holds (component rendering), the fixed template produces the expected behavior (successful rendering with JSON-LD output).

**Pseudocode:**
```
FOR ALL componentConfig WHERE isBugCondition(componentConfig) DO
  result := renderComponent_fixed(componentConfig)
  ASSERT result.noErrors AND result.containsJsonLd
END FOR
```

### Preservation Checking

**Goal**: Verify that for all component configurations, the fixed template produces exactly the same JSON-LD output and HTML structure as the original component would have produced (if it hadn't crashed).

**Pseudocode:**
```
FOR ALL componentConfig IN [organization, website, person, breadcrumb] DO
  expectedOutput := getExpectedJsonLd(componentConfig)
  actualOutput := renderComponent_fixed(componentConfig)
  ASSERT actualOutput.jsonLd == expectedOutput
  ASSERT actualOutput.scriptTagFormat == expectedOutput.scriptTagFormat
END FOR
```

**Testing Approach**: Property-based testing is recommended for preservation checking because:
- It generates many test cases automatically across different schema types and configurations
- It catches edge cases like null data, invalid schemas, and empty breadcrumbs
- It provides strong guarantees that the JSON-LD output is unchanged for all valid inputs

**Test Plan**: Since we cannot observe behavior on UNFIXED code (it crashes), we will use the component class's `getJsonLd()` method directly to determine expected output, then verify the fixed template produces identical output.

**Test Cases**:
1. **Organization Schema Preservation**: Call `getJsonLd()` directly on component instance, verify template output matches
2. **Website Schema Preservation**: Call `getJsonLd()` directly on component instance, verify template output matches
3. **Person Schema Preservation**: Call `getJsonLd()` directly with mock Memorial data, verify template output matches
4. **Breadcrumb Schema Preservation**: Call `getJsonLd()` directly with breadcrumb array, verify template output matches
5. **Null Output Preservation**: When `getJsonLd()` returns null, verify no script tag is rendered

### Unit Tests

- Test component rendering with each schema type (organization, website, person, breadcrumb)
- Test edge cases (null data, invalid schema, empty breadcrumbs)
- Test that the script tag format is correct
- Test that conditional rendering works (no output when getJsonLd returns null)

### Property-Based Tests

- Generate random component configurations and verify successful rendering
- Generate random Memorial data for person schema and verify JSON-LD structure
- Generate random breadcrumb arrays and verify breadcrumb schema output
- Test that all valid configurations produce valid JSON-LD output

### Integration Tests

- Test full page rendering with organization schema in layout
- Test Memorial detail page with person schema
- Test navigation pages with breadcrumb schema
- Test that multiple schema types can coexist on the same page
