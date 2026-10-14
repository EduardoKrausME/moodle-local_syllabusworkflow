import Notification from 'core/notification';
/** Copy a plan URL on explicit user interaction. */
export const init = () => {
    document.querySelectorAll('[data-swf-copy]').forEach((element) => {
        element.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(element.dataset.swfCopy);
            } catch (error) {
                Notification.exception(error);
            }
        });
    });
};
