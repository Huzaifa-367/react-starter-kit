import React, { useState, useEffect } from 'react';
import { useForm, Head, router } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { toast } from 'sonner';
import TextLink from '@/components/text-link';
import { login } from '@/routes';
import { Loader2 } from 'lucide-react';

interface Props {
    purpose: string;
    contact: string;
    email?: string;
    expires_in_seconds?: number;
}

export default function OtpVerify({
    purpose,
    contact,
    email,
    expires_in_seconds = 600,
}: Props) {
    const [timer, setTimer] = useState<number>(expires_in_seconds);
    const [resending, setResending] = useState<boolean>(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        purpose: purpose,
        email: email || '',
    });

    // Countdown Timer Effect
    useEffect(() => {
        if (timer <= 0) return;

        const interval = setInterval(() => {
            setTimer((prev) => prev - 1);
        }, 1000);

        return () => clearInterval(interval);
    }, [timer]);

    // Format time: MM:SS
    const formatTime = (seconds: number) => {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${remainingSeconds
            .toString()
            .padStart(2, '0')}`;
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/verify/otp', {
            onError: () => reset('code'),
        });
    };

    const handleResend = () => {
        if (timer > 0 || resending) return;

        setResending(true);
        router.post(
            '/verify/otp/resend',
            { purpose, email },
            {
                onSuccess: () => {
                    toast.success('Verification code resent.');
                    setTimer(expires_in_seconds);
                    reset('code');
                    setResending(false);
                },
                onError: (errors) => {
                    const message = errors.email || errors.code || 'Failed to resend code.';
                    toast.error(message);
                    setResending(false);
                },
            }
        );
    };

    return (
        <>
            <Head title="OTP Verification" />

            <div className="space-y-6">
                <div className="text-center">
                    <p className="text-sm text-muted-foreground">
                        We sent a 6-digit verification code to{' '}
                        <span className="font-semibold text-foreground">{contact}</span>.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="flex flex-col items-center justify-center space-y-3 text-center">
                        <InputOTP
                            maxLength={6}
                            value={data.code}
                            onChange={(value) => setData('code', value)}
                            disabled={processing}
                            pattern={REGEXP_ONLY_DIGITS}
                            autoFocus
                        >
                            <InputOTPGroup>
                                {Array.from({ length: 6 }, (_, index) => (
                                    <InputOTPSlot key={index} index={index} />
                                ))}
                            </InputOTPGroup>
                        </InputOTP>

                        <InputError message={errors.code} />
                        <InputError message={errors.email} />
                        <InputError message={errors.purpose} />
                    </div>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={processing || data.code.length < 6}
                    >
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Verify Code
                    </Button>
                </form>

                <div className="flex flex-col items-center justify-center space-y-4 pt-2 text-center text-sm text-muted-foreground">
                    {timer > 0 ? (
                        <p>
                            Code expires in:{' '}
                            <span className="font-mono font-medium text-foreground">
                                {formatTime(timer)}
                            </span>
                        </p>
                    ) : (
                        <div className="flex flex-col items-center space-y-1">
                            <p>Didn't receive the code?</p>
                            <button
                                type="button"
                                onClick={handleResend}
                                disabled={resending}
                                className="cursor-pointer font-medium text-primary underline underline-offset-4 hover:text-primary/95 disabled:opacity-50"
                            >
                                {resending ? 'Resending...' : 'Resend Code'}
                            </button>
                        </div>
                    )}

                    <div className="pt-2">
                        <TextLink href={login()}>Back to Log In</TextLink>
                    </div>
                </div>
            </div>
        </>
    );
}

OtpVerify.layout = {
    title: 'Verification Code Required',
    description: 'Please enter the 6-digit code sent to your contact details',
};
