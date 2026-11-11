# Linking anonymous form responses to a client

A respondent and a client are separate people. For example, John can fill out a public form without signing in and select JOYS as the company he wants to work with. The submission's `user_id` remains null; JOYS's account ID is stored as an answer to the client-selection question.

## Set up a form

1. Add a Dropdown question such as “Which client would you like to work with?”
2. Populate it from Accounts / Clients, using the name as the display column. Options submit unique account IDs. Role and account-status filters can narrow the list.
3. In the dropdown's properties, enable **Link responses to the selected client**. This selects it as the form's client reference question and makes it optional.
4. Alternatively, choose it under **Form Settings → Client response sharing → Client reference question**. Keep the question optional to allow submissions with no selected client.
5. Save and publish the form. Attach the form to a contract and assign the relevant clients to that contract.

Selecting a client does not require the respondent to log in. The form's existing anonymous-submission settings continue to apply. A name or contact details can be collected using ordinary questions, independently of account authentication.

## What clients can see

The client dashboard's **Responses linked to you** section lists a response only when both conditions are met:

- The response selects that client's exact account ID at the configured question's stable `field_key`.
- The form is currently attached to a contract assigned to that client.

Clients can read matching responses and download their attachments through dedicated client routes. They do not gain access to the owner's response list, exports, delete actions, or other clients' responses. Unassigning the client or detaching the form removes client access to those responses.

Leaving the client question blank keeps the response unlinked. The form owner can still manage it normally.

## Published versions

The client reference field is saved in each immutable published version's settings. Changing a question's visible label does not change its identity. Responses are matched using the configuration of the version submitted, so later draft edits cannot redirect historical responses to a different client question.

Existing published forms have no client reference by default. Configure the question and republish to enable linking for new responses; older responses from unconfigured versions are not automatically shared.
