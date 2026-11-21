import { defineStore } from 'pinia';

export interface PromptDraft {
    prompt: string;
    size: string;
    quality: string;
    reference_image_urls: string[];
}

const storageKey = 'pixelforge:prompt-draft';

function emptyDraft(): PromptDraft {
    return {
        prompt: '',
        size: '1024x1024',
        quality: 'medium',
        reference_image_urls: [''],
    };
}

function readDraft(): PromptDraft {
    if (typeof window === 'undefined') {
        return emptyDraft();
    }

    try {
        const storedDraft = window.sessionStorage.getItem(storageKey);

        if (storedDraft === null) {
            return emptyDraft();
        }

        const parsedDraft: unknown = JSON.parse(storedDraft);

        if (typeof parsedDraft !== 'object' || parsedDraft === null) {
            return emptyDraft();
        }

        const draft = parsedDraft as Partial<PromptDraft>;

        return {
            prompt: typeof draft.prompt === 'string' ? draft.prompt : '',
            size: ['1024x1024', '1024x1536', '1536x1024'].includes(draft.size ?? '')
                ? draft.size!
                : '1024x1024',
            quality: ['low', 'medium', 'high'].includes(draft.quality ?? '')
                ? draft.quality!
                : 'medium',
            reference_image_urls: Array.isArray(draft.reference_image_urls)
                ? draft.reference_image_urls.filter(
                      (url): url is string => typeof url === 'string',
                  ).slice(0, 4)
                : [''],
        };
    } catch {
        return emptyDraft();
    }
}

export const usePromptDraftStore = defineStore('promptDraft', {
    state: () => ({
        draft: emptyDraft(),
    }),
    actions: {
        restore(): PromptDraft {
            this.draft = readDraft();

            return {
                ...this.draft,
                reference_image_urls: [...this.draft.reference_image_urls],
            };
        },
        save(draft: PromptDraft): void {
            this.draft = {
                ...draft,
                reference_image_urls: [...draft.reference_image_urls],
            };

            if (typeof window === 'undefined') {
                return;
            }

            try {
                if (
                    this.draft.prompt.length === 0 &&
                    this.draft.reference_image_urls.every((url) => url.length === 0) &&
                    this.draft.size === '1024x1024' &&
                    this.draft.quality === 'medium'
                ) {
                    window.sessionStorage.removeItem(storageKey);

                    return;
                }

                window.sessionStorage.setItem(storageKey, JSON.stringify(this.draft));
            } catch {
                return;
            }
        },
        clear(): void {
            this.draft = emptyDraft();

            if (typeof window !== 'undefined') {
                try {
                    window.sessionStorage.removeItem(storageKey);
                } catch {
                    return;
                }
            }
        },
    },
});
