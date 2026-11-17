# Customize a check for a case

On an admin or client case page, expand a check and select **Customize fields**.
Change its display name, rename sections and field labels, add fields or sections,
and use the arrow buttons to arrange them. Choose short/long text, number, email,
phone, date, document upload, dropdown, single choice or checkbox. Choices, helper
text, placeholders and required candidate answers can be configured per field.
Document fields accept multiple files and folder selection.

Select **Save field layout** to apply the layout to this case's candidate form,
case view and future PDF reports. Clients may customize only cases belonging to
their organization. Agency findings and verification status remain admin controls.
The administrator's global templates are not changed by a case customization.

Renaming and arranging fields retain their existing answers and documents. Removing
a field hides it from the active form and report; its saved value remains in storage.
Uploaded evidence remains available even if its upload field is removed. **Restore
default fields** restores the original template and check name without erasing data.
An empty section is omitted when saved.

Clients can use **Edit details** to enter the case's stated information before a
report is finalized. Finalized stated information is locked, while field labels and
layout can still be customized. Candidate submissions enforce configured required
fields, answer types and choices; an existing document satisfies a required upload.

Deployment: run `php artisan migrate --force` for the nullable check display-name
column, and deploy the newly built `public/build` assets along with the PHP/Blade
changes. No re-upload of existing evidence is needed.
