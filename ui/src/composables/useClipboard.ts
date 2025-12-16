import { useMessage } from 'naive-ui';

export function useClipboard() {
    const message = useMessage();

    const copy = async (text: string) => {
        try {
            await navigator.clipboard.writeText(text);
            message.success('Copied to clipboard!', { duration: 2000 });
            return true;
        } catch (e) {
            message.error('Failed to copy to clipboard');
            return false;
        }
    };

    return { copy };
}
