# Implementation Plan

- [x] 1. Write bug condition exploration test
  - **Property 1: Fault Condition** - Component Renders Without $this Error
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate the "$this when not in object context" fatal error
  - **Scoped PBT Approach**: Scope the property to concrete failing cases - rendering the component with any schema type (organization, website, person, breadcrumb)
  - Test that rendering the SEO structured data component crashes with "$this when not in object context" error for all schema types
  - Test cases: organization schema, website schema, person schema with mock data, breadcrumb schema with breadcrumb array
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS with fatal error (this is correct - it proves the bug exists)
  - Document counterexamples found: "Fatal error: Using $this when not in object context at line 2 of structured-data.blade.php"
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 2.1, 2.2, 2.3_

- [x] 2. Write preservation property tests (BEFORE implementing fix)
  - **Property 2: Preservation** - JSON-LD Output Unchanged
  - **IMPORTANT**: Follow observation-first methodology
  - Since unfixed code crashes, observe expected behavior by calling getJsonLd() method directly on component instance
  - Write property-based tests capturing expected JSON-LD output patterns from Preservation Requirements
  - Test cases: organization schema output, website schema output, person schema with Memorial data, breadcrumb schema with breadcrumb array, null output when invalid schema
  - Property-based testing generates many test cases for stronger guarantees
  - Verify expected outputs match component class method results (baseline behavior to preserve)
  - **EXPECTED OUTCOME**: Tests define the expected JSON-LD output that must be preserved
  - Mark task complete when tests are written with expected outputs defined
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 3. Fix for SEO structured data $this context error

  - [x] 3.1 Implement the fix
    - Change line 2 in resources/views/components/seo/structured-data.blade.php
    - Replace `$jsonLd = $this->getJsonLd();` with `$jsonLd = $getJsonLd();`
    - Use correct Blade component syntax where methods are exposed as callable variables
    - No changes to component class or service layer required
    - _Bug_Condition: isBugCondition(input) where input.template contains "$this->getJsonLd()" AND input.contextType == "BladeComponentTemplate" AND NOT input.hasThisContext_
    - _Expected_Behavior: Component renders successfully using $getJsonLd() syntax, producing JSON-LD script tag without errors_
    - _Preservation: JSON-LD output format, script tag structure, conditional rendering, and all schema types remain unchanged_
    - _Requirements: 2.1, 2.2, 2.3, 3.1, 3.2, 3.3, 3.4_

  - [x] 3.2 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - Component Renders Without $this Error
    - **IMPORTANT**: Re-run the SAME test from task 1 - do NOT write a new test
    - The test from task 1 encodes the expected behavior
    - When this test passes, it confirms the expected behavior is satisfied
    - Run bug condition exploration test from step 1
    - **EXPECTED OUTCOME**: Test PASSES (confirms bug is fixed - component renders successfully for all schema types)
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 3.3 Verify preservation tests still pass
    - **Property 2: Preservation** - JSON-LD Output Unchanged
    - **IMPORTANT**: Re-run the SAME tests from task 2 - do NOT write new tests
    - Run preservation property tests from step 2
    - **EXPECTED OUTCOME**: Tests PASS (confirms JSON-LD output matches expected format for all schema types, no regressions)
    - Confirm all tests still pass after fix (no regressions)

- [x] 4. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
