import type {FontsourceFontFamily, Options} from 'unplugin-fonts/types';

// npm install @fontsource/poppins
const poppins: FontsourceFontFamily = {
    name: 'Poppins',
    weights: [300, 400, 500, 600, 700, 800, 900],
};
export default {
    display: 'auto',
    fontsource: {
        families: [
            poppins,
            {
                ...poppins,
                styles: ['italic'],
            },
        ],
    },
} as Options;
