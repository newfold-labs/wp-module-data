{
    let deactivationSurveyDialog;

    const renderDialog = () => {
        const surveyDialog = document.createElement('div');
        surveyDialog.id = 'nfd-deactivation-survey';
        surveyDialog.setAttribute('aria-labelledby', 'nfd-deactivation-survey-title');
        surveyDialog.setAttribute('aria-hidden', 'true');
        surveyDialog.innerHTML = getDialogHTML();

        const wpAdmin = document.querySelector('body.wp-admin');
        wpAdmin.appendChild(surveyDialog);

        deactivationSurveyDialog = new A11yDialog(surveyDialog);
        deactivationSurveyDialog.show();
    }

    const getDialogHTML = () => {
        const dialogHTML = `
        <div class="nfd-deactivation-survey__overlay" nfd-deactivation-survey-destroy></div>
        <div class="nfd-deactivation-survey__container" role="document">
            <div class="nfd-deactivation-survey__content">
                <h1 id="nfd-deactivation-survey-title" class="nfd-hidden" aria-hidden="true">${newfoldDataDeactivationSurvey.strings.surveyTitle}</h1>
                <div class="nfd-deactivation-survey__content-header">
                    <h3>${newfoldDataDeactivationSurvey.strings.dialogTitle}</h3>
                    <p>${newfoldDataDeactivationSurvey.strings.dialogDesc}</p>
                </div>
                ${getSurveyFormHTML()}
                <span class="nfd-deactivation-survey_loading nfd-hidden"></span>
            </div>
        </div>
        <div class="nfd-deactivation-survey__disabled nfd-hidden"></div>
        `;
        return dialogHTML;
    }

    const getSurveyFormHTML = () => {
        return `
        <form aria-label="${newfoldDataDeactivationSurvey.strings.formAriaLabel}">
            <fieldset>
                <label for="nfd-deactivation-survey__input">${newfoldDataDeactivationSurvey.strings.label}</label>
                <textarea id="nfd-deactivation-survey__input" placeholder="${newfoldDataDeactivationSurvey.strings.placeholder}"></textarea>
            </fieldset>
            <div class="nfd-deactivation-survey__content-actions">
                <div>
                    <input type="submit" value="${newfoldDataDeactivationSurvey.strings.submit}" nfd-deactivation-survey-submit class="button button-primary" aria-label="${newfoldDataDeactivationSurvey.strings.submitAriaLabel}"/>
                    <button type="button" class="nfd-deactivation-survey-action" nfd-deactivation-survey-destroy aria-label="${newfoldDataDeactivationSurvey.strings.cancelAriaLabel}">${newfoldDataDeactivationSurvey.strings.cancel}</button>
                </div>
                <div>
                    <button type="button" class="nfd-deactivation-survey-action" nfd-deactivation-survey-skip aria-label="${newfoldDataDeactivationSurvey.strings.skipAriaLabel}">${newfoldDataDeactivationSurvey.strings.skip}</button>
                </div>
            </div>
        </form>
        `;
    }

    const destroyDialog = () => {
        deactivationSurveyDialog.destroy();
        deactivationSurveyDialog = null;

        const dialog = document.getElementById('nfd-deactivation-survey');
        if (dialog) {
            dialog.remove();
        }
    }

    const deactivatePlugin = () => {
        destroyDialog();
        const deactivateLink = document.getElementById('deactivate-' + newfoldDataDeactivationSurvey.pluginSlug).href;
        if (deactivateLink) {
            window.location.href = deactivateLink;
        } else {
            console.error('Error: Deactivation link not found.');
        }
    }

    const isSubmitting = () => {
        const dialogDisabledOverlay = document.querySelector('.nfd-deactivation-survey__disabled');
        dialogDisabledOverlay.classList.remove('nfd-hidden');
        const dialogLoading = document.querySelector('.nfd-deactivation-survey_loading');
        dialogLoading.classList.remove('nfd-hidden');
        const actionsBtns = [
            ...document.querySelectorAll('.nfd-deactivation-survey-action'),
            document.querySelector('#nfd-deactivation-survey form input[type="submit"]'),
        ];
        actionsBtns.forEach(btn => {
            btn.setAttribute('disabled', 'true');
        });

        // disbale ESC key while submitting
        deactivationSurveyDialog.on('show', () => {
            deactivationSurveyDialog.off('keydown');
        });
    }

    const submitSurvey = async () => {
        isSubmitting();
        const surveyInput = document.getElementById('nfd-deactivation-survey__input').value;

        const send = await sendEvent(surveyInput);
        deactivatePlugin();
    }

    const sendEvent = async (surveyInput) => {
        await fetch(newfoldDataDeactivationSurvey.eventsEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': newfoldDataDeactivationSurvey.restApiNonce
            },
            body: JSON.stringify({
                action: 'deactivation_survey_freeform',
                data: {
                    surveyInput: surveyInput.length > 0 ? surveyInput : 'No input',
                    brand: newfoldDataDeactivationSurvey.brand,
                    page: window.location.href
                }
            })
        });
        return true;
    }

    // Attach events listeners
    window.addEventListener('DOMContentLoaded', () => {
        const wpAdmin = document.querySelector('body.wp-admin');
        wpAdmin.addEventListener('click', (e) => {
            // Plugin deactivation listener
            if (e.target.id === 'deactivate-' + newfoldDataDeactivationSurvey.pluginSlug) {
                e.preventDefault();
                renderDialog();
            }

            // Remove dialog listener
            if (e.target.hasAttribute('nfd-deactivation-survey-destroy')) {
                destroyDialog();
            }

            // Submit listener
            if (e.target.hasAttribute('nfd-deactivation-survey-submit')) {
                e.preventDefault();
                submitSurvey();
            }

            // Skip listener
            if (e.target.hasAttribute('nfd-deactivation-survey-skip')) {
                e.preventDefault();
                deactivatePlugin();
            }
        });
    })
}