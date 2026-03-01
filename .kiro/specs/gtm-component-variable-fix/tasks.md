# Implementation Plan

- [ ] 1. Write bug condition exploration test
  - **Property 1: Fault Condition** - GTM Components Render Without Errors
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate the bug exists
  - **Scoped PBT Approach**: Scope the property to concrete failing cases - GTM components rendering with private $gtmService property
  - Test that GTMHead component can render and access $gtmService->isEnabled() without "Undefined variable" error
  - Test that GTMBody component can render and access $gtmService->isEnabled() without "Undefined variable" error
  - Test that GTMHead with valid container ID outputs head script without template errors
  - Test that GTMBody with valid container ID outputs body noscript without template errors
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS with "Undefined variable $gtmService" errors (this is correct - it proves the bug exists)
  - Document counterexamples found to understand root cause (private property visibility preventing template access)
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 1.1, 1.2, 1.3_

- [ ] 2. Write preservation property tests (BEFORE implementing fix)
  - **Property 2: Preservation** - GTM Functionality Unchanged
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for non-buggy scenarios (if accessible through direct service calls)
  - Write property-based tests capturing GTM configuration behavior patterns:
    - GTM enabled with valid container ID → head script contains correct container ID
    - GTM disabled → no scripts output (empty strings)
    - Local environment → GTM disabled regardless of config
    - Different container IDs → scripts contain correct IDs
  - Property-based testing generates many test cases for stronger guarantees across GTM configurations
  - Run tests on GTMService directly (bypassing component rendering) to establish baseline behavior
  - **EXPECTED OUTCOME**: Tests PASS when testing GTMService directly (this confirms baseline GTM logic to preserve)
  - Mark task complete when tests are written, run, and passing on GTMService baseline
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [ ] 3. Fix for GTM component variable accessibility

  - [ ] 3.1 Change property visibility in GTMHead component
    - Open app/View/Components/Analytics/GTMHead.php
    - Change constructor parameter from `private GTMService $gtmService` to `public GTMService $gtmService`
    - No other changes needed in this file
    - _Bug_Condition: isBugCondition(input) where input.component == 'GTMHead' AND input.component.gtmService.visibility == 'private'_
    - _Expected_Behavior: Component renders without errors and makes $gtmService accessible to template_
    - _Preservation: GTM enabled/disabled logic, container ID handling, script output format remain unchanged_
    - _Requirements: 2.1, 2.3, 3.1, 3.2, 3.3, 3.4_

  - [ ] 3.2 Change property visibility in GTMBody component
    - Open app/View/Components/Analytics/GTMBody.php
    - Change constructor parameter from `private GTMService $gtmService` to `public GTMService $gtmService`
    - No other changes needed in this file
    - _Bug_Condition: isBugCondition(input) where input.component == 'GTMBody' AND input.component.gtmService.visibility == 'private'_
    - _Expected_Behavior: Component renders without errors and makes $gtmService accessible to template_
    - _Preservation: GTM enabled/disabled logic, container ID handling, script output format remain unchanged_
    - _Requirements: 2.2, 2.3, 3.1, 3.2, 3.3, 3.4_

  - [ ] 3.3 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - GTM Components Render Without Errors
    - **IMPORTANT**: Re-run the SAME test from task 1 - do NOT write a new test
    - The test from task 1 encodes the expected behavior
    - When this test passes, it confirms the expected behavior is satisfied
    - Run bug condition exploration test from step 1
    - **EXPECTED OUTCOME**: Test PASSES (confirms bug is fixed - components render without "Undefined variable" errors)
    - _Requirements: 2.1, 2.2, 2.3_

  - [ ] 3.4 Verify preservation tests still pass
    - **Property 2: Preservation** - GTM Functionality Unchanged
    - **IMPORTANT**: Re-run the SAME tests from task 2 - do NOT write new tests
    - Run preservation property tests from step 2
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions - GTM logic unchanged)
    - Confirm all tests still pass after fix (GTM enabled/disabled, container IDs, script output all work correctly)

- [ ] 4. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
