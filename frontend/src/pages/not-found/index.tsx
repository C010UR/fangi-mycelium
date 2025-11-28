import { Link } from '@tanstack/react-router';
import { Button } from '@/components/ui/button';
import { MoveLeft, Home } from 'lucide-react';

export default function NotFoundPage() {
  return (
    <div className="min-h-screen w-full flex flex-col items-center justify-center bg-background relative overflow-hidden">
      {/* Abstract Background Elements */}
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-primary/5 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-accent/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/4 pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[500px] h-[500px] bg-secondary/10 rounded-full blur-3xl translate-y-1/2 -translate-x-1/4 pointer-events-none" />

      {/* Logo Header */}
      <div className="absolute top-8 left-8 flex items-center gap-3">
        <div className="h-10 w-10 bg-primary/10 rounded-xl flex items-center justify-center">
          <img src="/logo.svg" alt="Fangi logo" className="h-6 w-6" />
        </div>
        <span className="text-xl font-bold tracking-tight text-foreground/80">Fangi</span>
      </div>

      <div className="relative z-10 container mx-auto px-4 flex flex-col items-center text-center">
        <h1 className="text-[12rem] md:text-[16rem] font-black leading-none text-transparent bg-clip-text bg-linear-to-b from-foreground/5 to-foreground/0 select-none pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 -z-10">
          404
        </h1>

        <div className="space-y-6 max-w-lg">
          <div className="space-y-2">
            <h2 className="text-4xl md:text-5xl font-bold tracking-tight text-foreground">
              Page not found
            </h2>
            <p className="text-lg text-muted-foreground leading-relaxed">
              Sorry, we couldn't find the page you're looking for. It might have been moved,
              deleted, or never existed.
            </p>
          </div>

          <div className="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
            <Button
              asChild
              size="lg"
              className="rounded-full px-8 h-12 text-base font-medium shadow-lg hover:shadow-primary/25 transition-all"
            >
              <Link to="/">
                <Home className="mr-2 h-4 w-4" />
                Back to Dashboard
              </Link>
            </Button>
            <Button
              asChild
              variant="ghost"
              size="lg"
              className="rounded-full px-8 h-12 text-base font-medium hover:bg-muted"
            >
              <Link to="..">
                <MoveLeft className="mr-2 h-4 w-4" />
                Go Back
              </Link>
            </Button>
          </div>
        </div>
      </div>

      <div className="absolute bottom-8 text-sm text-muted-foreground/50">
        Error Code: 404 • Not Found
      </div>
    </div>
  );
}
