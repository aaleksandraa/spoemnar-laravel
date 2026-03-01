# CSP Nonce Undefined Fix - Bugfix Design

## Overview

The application crashes with "Call to undefined function csp_nonce()" errors when analytics components are rendered. The bug occurs because three files (data-layer-init.blade.php, gtm-head.blade.php, and CSPCompatibilityTest.php) call a csp_nonce() helper function that does not exist in the codebase. The application currently uses SecurityHeaders middleware with a CSP policy that allows 'unsafe-inline' for scripts, making nonce-based CSP unnecessary for the current security posture. The fix will remove all calls to the non-existent csp_nonce() function and update the GTMService to handle null nonce values correctly, which it already does.

## Glossary

- **Bug_Condition (C)**: The condition that triggers the bug - when analytics components attempt to call the non-existent csp_nonce() function
- **Property (P)**: The desired behavior when analytics components are rendered - they should render successfully without calling csp_nonce()
- **Preservation**: Existing GTM functionality, CSP directives, and test coverage that must remain unchanged by the fix
- **csp_nonce()**: A non-existent helper function that was intended to provide CSP nonces but was never implemented
- **GTMService**: The service class in `app/Services/Analytics/GTMService.php` that generates GTM script tags and accepts an optional nonce parameter
- **SecurityHeaders**: The middleware in `app/Http/Middleware/SecurityHeaders.php` that sets CSP headers with 'unsafe-inline' policy

## Bug Details

### Fault Condition

The bug manifests when any of the three analytics components are rendered or when the CSPCompatibilityTest test suite is executed. The components call csp_nonce() which does not exist, causing a fatal PHP error. The GTMService::getHeadScript() method is designed to accept null for the nonce parameter and already handles this case correctly by omitting the nonce attribute.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type ComponentRenderContext OR TestExecutionContext
  OUTPUT: boolean
  
  RETURN (input.component IN ['data-layer-init.blade.php', 'gtm-head.blade.php'] 
         AND input.attemptsToCspNonce = true)
         OR (input.testClass = 'CSPCompatibilityTest' 
         AND input.testMethod = 'test_data_layer_initialization_uses_nonce')
END FUNCTION
```

### Examples

- **data-layer-init.blade.php rendering**: When this component is included in a page, line 5 attempts to call csp_nonce() to add a nonce attribute to the script tag, causing "Call to undefined function csp_nonce()" fatal error
- **gtm-head.blade.php rendering**: When this component is included in a page, it attempts to call csp_nonce() and pass the result to GTMService::getHeadScript(), causing "Call to undefined function csp_nonce()" fatal error
- **CSPCompatibilityTest execution**: When test_data_layer_initialization_uses_nonce() runs, it expects csp_nonce() to exist in the data-layer-init.blade.php template, but the function doesn't exist
- **Edge case - GTMService with null nonce**: GTMService::getHeadScript(null) already works correctly and generates script tags without nonce attributes (expected behavior after fix)

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- GTMService::getHeadScript(null) must continue to generate script tags without nonce attributes as designed
- GTMService::getBodyNoScript() must continue to generate noscript iframe tags correctly
- SecurityHeaders middleware must continue to apply the CSP policy with 'unsafe-inline' for scripts
- All other CSPCompatibilityTest tests must continue to pass, validating GTM functionality, CSP directives, and domain whitelisting

**Scope:**
All functionality that does NOT involve the csp_nonce() function should be completely unaffected by this fix. This includes:
- GTM script generation and rendering
- CSP directive configuration and retrieval
- Analytics domain whitelisting
- GTM enable/disable logic based on environment
- All other test cases in CSPCompatibilityTest

## Hypothesized Root Cause

Based on the bug description and code analysis, the root causes are:

1. **Missing Helper Function**: The csp_nonce() helper function was never implemented, despite being called in three locations
   - No CSP package providing this function is installed
   - No custom helper file defines this function
   - The function was likely planned but never completed

2. **Unnecessary Nonce Usage**: The application's current CSP policy uses 'unsafe-inline' for scripts
   - SecurityHeaders middleware sets: script-src 'self' 'unsafe-inline' 'unsafe-eval'
   - With 'unsafe-inline', nonce-based CSP provides no additional security benefit
   - The nonce functionality is not needed for the current security posture

3. **Test Expectations Mismatch**: CSPCompatibilityTest expects nonce functionality that doesn't exist
   - test_data_layer_initialization_uses_nonce() validates that csp_nonce() is used
   - This test validates a feature that was never implemented
   - The test should be removed or updated to reflect actual implementation

4. **GTMService Already Handles Null**: The GTMService is already designed to work without nonces
   - getHeadScript() accepts ?string $nonce = null parameter
   - When null, it generates script tags without nonce attributes
   - The service doesn't require nonce functionality to work correctly

## Correctness Properties

Property 1: Fault Condition - Analytics Components Render Without Crashing

_For any_ component rendering context where an analytics component (data-layer-init.blade.php or gtm-head.blade.php) is rendered, the fixed components SHALL render successfully without calling csp_nonce(), generating script tags without nonce attributes, and without throwing any errors.

**Validates: Requirements 2.1, 2.2**

Property 2: Preservation - Existing GTM and CSP Functionality

_For any_ GTM operation or CSP configuration that does NOT involve the csp_nonce() function, the fixed code SHALL produce exactly the same behavior as the original code, preserving GTM script generation, CSP directive configuration, domain whitelisting, and all passing test cases.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4**

## Fix Implementation

### Changes Required

Assuming our root cause analysis is correct:

**File 1**: `resources/views/components/analytics/data-layer-init.blade.php`

**Specific Changes**:
1. **Remove csp_nonce() call**: Replace the script tag that calls csp_nonce() with a simple script tag without nonce attribute
   - Current: `<script{!! csp_nonce() ? ' nonce="' . csp_nonce() . '"' : '' !!}>`
   - Fixed: `<script>`

**File 2**: `resources/views/components/analytics/gtm-head.blade.php`

**Specific Changes**:
1. **Pass null to GTMService**: Remove the csp_nonce() call and pass null explicitly to getHeadScript()
   - Current: `{!! $gtmService->getHeadScript(csp_nonce() ?? null) !!}`
   - Fixed: `{!! $gtmService->getHeadScript(null) !!}`

**File 3**: `tests/Feature/Analytics/CSPCompatibilityTest.php`

**Specific Changes**:
1. **Remove nonce expectation test**: Delete the test_data_layer_initialization_uses_nonce() method entirely
   - This test validates functionality that doesn't exist and isn't needed
   - All other tests should remain unchanged

2. **Update inline scripts test**: Modify test_no_inline_scripts_without_nonces_in_gtm_components() to reflect that nonce functionality is not implemented
   - Remove assertions that expect nonce usage in templates
   - Keep assertions that validate GTMService behavior and noscript generation

**No Changes Required**:
- GTMService.php already handles null nonce correctly
- SecurityHeaders.php CSP policy doesn't need changes
- All other test methods remain unchanged

## Testing Strategy

### Validation Approach

The testing strategy follows a two-phase approach: first, surface counterexamples that demonstrate the bug on unfixed code (fatal errors when rendering components), then verify the fix works correctly (components render without errors) and preserves existing behavior (GTM functionality and other tests continue to pass).

### Exploratory Fault Condition Checking

**Goal**: Surface counterexamples that demonstrate the bug BEFORE implementing the fix. Confirm that the root cause is the missing csp_nonce() function. If we refute this, we will need to re-hypothesize.

**Test Plan**: Attempt to render each analytics component in a test environment and observe the fatal errors. Run the CSPCompatibilityTest suite on the UNFIXED code to observe which tests fail due to the missing function.

**Test Cases**:
1. **data-layer-init.blade.php Rendering Test**: Render the component with test data (will fail on unfixed code with "Call to undefined function csp_nonce()")
2. **gtm-head.blade.php Rendering Test**: Render the component with GTM enabled (will fail on unfixed code with "Call to undefined function csp_nonce()")
3. **CSPCompatibilityTest Execution**: Run test_data_layer_initialization_uses_nonce() (will fail on unfixed code because csp_nonce() doesn't exist in template)
4. **GTMService Null Nonce Test**: Call GTMService::getHeadScript(null) directly (should pass on unfixed code, confirming the service handles null correctly)

**Expected Counterexamples**:
- Fatal PHP errors: "Call to undefined function csp_nonce()" when rendering data-layer-init.blade.php
- Fatal PHP errors: "Call to undefined function csp_nonce()" when rendering gtm-head.blade.php
- Test failure: test_data_layer_initialization_uses_nonce() expects csp_nonce() in template
- Possible confirmation: GTMService::getHeadScript(null) works correctly, showing the service doesn't need nonce functionality

### Fix Checking

**Goal**: Verify that for all inputs where the bug condition holds, the fixed components produce the expected behavior (render successfully without errors).

**Pseudocode:**
```
FOR ALL input WHERE isBugCondition(input) DO
  result := renderComponent_fixed(input)
  ASSERT result.noErrors = true
  ASSERT result.scriptTagGenerated = true
  ASSERT result.nonceAttributeAbsent = true
END FOR
```

### Preservation Checking

**Goal**: Verify that for all inputs where the bug condition does NOT hold, the fixed code produces the same result as the original code (all GTM functionality and other tests continue to work).

**Pseudocode:**
```
FOR ALL input WHERE NOT isBugCondition(input) DO
  ASSERT originalBehavior(input) = fixedBehavior(input)
END FOR
```

**Testing Approach**: Property-based testing is recommended for preservation checking because:
- It generates many test cases automatically across the input domain
- It catches edge cases that manual unit tests might miss
- It provides strong guarantees that behavior is unchanged for all non-buggy inputs

**Test Plan**: Run all other CSPCompatibilityTest tests on UNFIXED code to observe they pass (except the nonce test), then verify they continue to pass after the fix.

**Test Cases**:
1. **GTM Script Generation Preservation**: Verify GTMService::getHeadScript(null) produces the same output before and after fix
2. **GTM Noscript Generation Preservation**: Verify GTMService::getBodyNoScript() produces the same output before and after fix
3. **CSP Directives Preservation**: Verify GTMService::getCspDirectives() returns the same directives before and after fix
4. **All Other CSPCompatibilityTest Tests**: Verify all tests except test_data_layer_initialization_uses_nonce() continue to pass

### Unit Tests

- Test that data-layer-init.blade.php renders without errors after fix
- Test that gtm-head.blade.php renders without errors after fix
- Test that GTMService::getHeadScript(null) generates script tags without nonce attributes
- Test that script tags in rendered components do not have nonce attributes

### Property-Based Tests

- Generate random initial state data for data-layer-init.blade.php and verify it renders successfully
- Generate random GTM configurations and verify gtm-head.blade.php renders successfully
- Generate random GTM service configurations and verify getHeadScript(null) produces valid HTML
- Test that all CSP-related functionality continues to work across many scenarios

### Integration Tests

- Test full page rendering with analytics components included
- Test GTM functionality in different environments (local, production)
- Test that analytics tracking works correctly after the fix
- Test that SecurityHeaders middleware continues to set CSP headers correctly
