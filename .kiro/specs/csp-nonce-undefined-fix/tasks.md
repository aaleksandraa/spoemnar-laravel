# Implementation Plan

- [x] 1. Write bug condition exploration test
  - **Property 1: Fault Condition** - Analytics Components Crash on csp_nonce() Call
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate the bug exists
  - **Scoped PBT Approach**: Scope the property to concrete failing cases - rendering data-layer-init.blade.php and gtm-head.blade.php components
  - Test that data-layer-init.blade.php renders without fatal errors (from Fault Condition in design)
  - Test that gtm-head.blade.php renders without fatal errors (from Fault Condition in design)
  - Test that CSPCompatibilityTest::test_data_layer_initialization_uses_nonce() can execute (from Fault Condition in design)
  - The test assertions should match the Expected Behavior Properties from design: components render successfully without calling csp_nonce()
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS with "Call to undefined function csp_nonce()" errors (this is correct - it proves the bug exists)
  - Document counterexamples found to understand root cause
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 2.1, 2.2_

- [x] 2. Write preservation property tests (BEFORE implementing fix)
  - **Property 2: Preservation** - Existing GTM and CSP Functionality
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for non-buggy inputs (GTM operations that don't involve csp_nonce())
  - Test that GTMService::getHeadScript(null) generates script tags without nonce attributes
  - Test that GTMService::getBodyNoScript() generates noscript iframe tags correctly
  - Test that GTMService::getCspDirectives() returns correct CSP directives
  - Test that SecurityHeaders middleware applies CSP policy with 'unsafe-inline' for scripts
  - Write property-based tests capturing observed behavior patterns from Preservation Requirements
  - Property-based testing generates many test cases for stronger guarantees
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (this confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 3. Fix for CSP nonce undefined function errors

  - [x] 3.1 Remove csp_nonce() call from data-layer-init.blade.php
    - Replace script tag that calls csp_nonce() with simple script tag without nonce attribute
    - Change from: `<script{!! csp_nonce() ? ' nonce="' . csp_nonce() . '"' : '' !!}>` to: `<script>`
    - _Bug_Condition: isBugCondition(input) where input.component = 'data-layer-init.blade.php' AND input.attemptsToCspNonce = true_
    - _Expected_Behavior: Component renders successfully without calling csp_nonce(), generating script tags without nonce attributes_
    - _Preservation: GTM functionality, CSP directives, and other test coverage remain unchanged_
    - _Requirements: 2.1, 3.1, 3.2, 3.3, 3.4_

  - [x] 3.2 Remove csp_nonce() call from gtm-head.blade.php
    - Pass null explicitly to GTMService::getHeadScript() instead of calling csp_nonce()
    - Change from: `{!! $gtmService->getHeadScript(csp_nonce() ?? null) !!}` to: `{!! $gtmService->getHeadScript(null) !!}`
    - _Bug_Condition: isBugCondition(input) where input.component = 'gtm-head.blade.php' AND input.attemptsToCspNonce = true_
    - _Expected_Behavior: Component renders successfully without calling csp_nonce(), GTMService handles null nonce correctly_
    - _Preservation: GTM script generation and CSP functionality remain unchanged_
    - _Requirements: 2.2, 3.1, 3.2, 3.3, 3.4_

  - [x] 3.3 Remove nonce expectation test from CSPCompatibilityTest
    - Delete test_data_layer_initialization_uses_nonce() method entirely
    - Update test_no_inline_scripts_without_nonces_in_gtm_components() to remove assertions expecting nonce usage in templates
    - Keep assertions validating GTMService behavior and noscript generation
    - _Bug_Condition: isBugCondition(input) where input.testClass = 'CSPCompatibilityTest' AND input.testMethod = 'test_data_layer_initialization_uses_nonce'_
    - _Expected_Behavior: Test suite executes without expecting non-existent csp_nonce() function_
    - _Preservation: All other CSPCompatibilityTest tests continue to pass_
    - _Requirements: 2.1, 2.2, 3.4_

  - [x] 3.4 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - Analytics Components Render Without Crashing
    - **IMPORTANT**: Re-run the SAME test from task 1 - do NOT write a new test
    - The test from task 1 encodes the expected behavior
    - When this test passes, it confirms the expected behavior is satisfied
    - Run bug condition exploration test from step 1
    - **EXPECTED OUTCOME**: Test PASSES (confirms bug is fixed - components render without csp_nonce() errors)
    - _Requirements: 2.1, 2.2_

  - [x] 3.5 Verify preservation tests still pass
    - **Property 2: Preservation** - Existing GTM and CSP Functionality
    - **IMPORTANT**: Re-run the SAME tests from task 2 - do NOT write new tests
    - Run preservation property tests from step 2
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions - GTM and CSP functionality unchanged)
    - Confirm all tests still pass after fix (no regressions)
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 4. Checkpoint - Ensure all tests pass
  - Verify all CSPCompatibilityTest tests pass (except removed test_data_layer_initialization_uses_nonce)
  - Verify data-layer-init.blade.php renders successfully in test environment
  - Verify gtm-head.blade.php renders successfully in test environment
  - Verify GTMService continues to generate correct script tags and noscript iframes
  - Verify SecurityHeaders middleware continues to apply CSP policy correctly
  - Ask the user if questions arise
